/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "zbxdiscovery.h"

#include "zbxserver.h"
#include "zbxtime.h"
#include "zbxnum.h"
#include "zbxserialize.h"

typedef struct
{
	zbx_uint64_t	dserviceid;
	int		status;
	int		lastup;
	int		lastdown;
	char		*value;
}
DB_DSERVICE;

#define DISCOVERER_INITIALIZED_YES	1

static int	discoverer_initialized = 0;

void	zbx_discoverer_init(void)
{
	discoverer_initialized = DISCOVERER_INITIALIZED_YES;
}

/******************************************************************************
 *                                                                            *
 * Purpose: free discovery check                                              *
 *                                                                            *
 ******************************************************************************/
void	zbx_discovery_dcheck_free(zbx_dc_dcheck_t *dcheck)
{
	zbx_free(dcheck->key_);
	zbx_free(dcheck->ports);

	if (SVC_SNMPv1 == dcheck->type || SVC_SNMPv2c == dcheck->type || SVC_SNMPv3 == dcheck->type)
	{
		zbx_free(dcheck->snmp_community);
		zbx_free(dcheck->snmpv3_securityname);
		zbx_free(dcheck->snmpv3_authpassphrase);
		zbx_free(dcheck->snmpv3_privpassphrase);
		zbx_free(dcheck->snmpv3_contextname);
	}

	zbx_free(dcheck);
}

/******************************************************************************
 *                                                                            *
 * Purpose: free discovery rule                                               *
 *                                                                            *
 ******************************************************************************/
void	zbx_discovery_drule_free(zbx_dc_drule_t *drule)
{
	zbx_free(drule->delay_str);
	zbx_free(drule->iprange);
	zbx_free(drule->name);

	zbx_vector_dc_dcheck_ptr_clear_ext(&drule->dchecks, zbx_discovery_dcheck_free);
	zbx_vector_dc_dcheck_ptr_destroy(&drule->dchecks);

	zbx_free(drule);
}

/******************************************************************************
 *                                                                            *
 * Purpose: sends command to discovery manager                                *
 *                                                                            *
 * Parameters: code     - [IN] message code                                   *
 *             data     - [IN] message data                                   *
 *             size     - [IN] message data size                              *
 *             response - [OUT] response message (can be NULL if response is  *
 *                              not requested)                                *
 *                                                                            *
 ******************************************************************************/
static void	discovery_send(zbx_uint32_t code, unsigned char *data, zbx_uint32_t size,
		zbx_ipc_message_t *response)
{
	char			*error = NULL;
	static zbx_ipc_socket_t	socket = {0};

	/* each process has a permanent connection to discovery manager */
	if (0 == socket.fd && FAIL == zbx_ipc_socket_open(&socket, ZBX_IPC_SERVICE_DISCOVERER, SEC_PER_MIN,
			&error))
	{
		zabbix_log(LOG_LEVEL_CRIT, "cannot connect to discoverer service: %s", error);
		exit(EXIT_FAILURE);
	}

	if (FAIL == zbx_ipc_socket_write(&socket, code, data, size))
	{
		zabbix_log(LOG_LEVEL_CRIT, "cannot send data to discoverer service");
		exit(EXIT_FAILURE);
	}

	if (NULL != response && FAIL == zbx_ipc_socket_read(&socket, response))
	{
		zabbix_log(LOG_LEVEL_CRIT, "cannot receive data from discoverer service");
		exit(EXIT_FAILURE);
	}
}

/******************************************************************************
 *                                                                            *
 * Purpose: get queue size (enqueued checks count) of discovery manager       *
 *                                                                            *
 * Parameters: size  - [OUT] enqueued item count                              *
 *             error - [OUT] error message                                    *
 *                                                                            *
 * Return value: SUCCEED - queue size retrieved                               *
 *               FAIL    - discovery manager is not initialized               *
 *                                                                            *
 ******************************************************************************/
int	zbx_discovery_get_queue_size(zbx_uint64_t *size, char **error)
{
	zbx_ipc_message_t	message;

	if (DISCOVERER_INITIALIZED_YES != discoverer_initialized)
	{
		if (NULL != error)
		{
			*error = zbx_strdup(NULL, "discoverer is not initialized: please check \"StartDiscoverers\""
					" configuration parameter");
		}

		return FAIL;
	}

	zbx_ipc_message_init(&message);
	discovery_send(ZBX_IPC_DISCOVERER_QUEUE, NULL, 0, &message);
	memcpy(size, message.data, sizeof(zbx_uint64_t));
	zbx_ipc_message_clean(&message);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Purpose: unpack worker usage statistics                                    *
 *                                                                            *
 * Parameters: usage - [OUT] the worker usage statistics                      *
 *             count - [OUT]                                                  *
 *             data  - [IN] the input data                                    *
 *                                                                            *
 ******************************************************************************/
static void	discovery_unpack_usage_stats(zbx_vector_dbl_t *usage, int *count, const unsigned char *data)
{
	const unsigned char	*offset = data;
	int			usage_num, i;

	offset += zbx_deserialize_value(offset, &usage_num);
	zbx_vector_dbl_reserve(usage, (size_t)usage_num);

	for (i = 0; i < usage_num; i++)
	{
		double	busy;

		offset += zbx_deserialize_value(offset, &busy);
		zbx_vector_dbl_append(usage, busy);
	}

	(void)zbx_deserialize_value(offset, count);
}

/******************************************************************************
 *                                                                            *
 * Purpose: get discovery manager diagnostic statistics                       *
 *                                                                            *
 ******************************************************************************/
int	zbx_discovery_get_usage_stats(zbx_vector_dbl_t *usage, int *count, char **error)
{
	unsigned char	*result;

	if (DISCOVERER_INITIALIZED_YES != discoverer_initialized)
	{
		*error = zbx_strdup(NULL, "discoverer is not initialized: please check \"StartDiscoverers\""
				" configuration parameter");

		return FAIL;
	}

	if (SUCCEED != zbx_ipc_async_exchange(ZBX_IPC_SERVICE_DISCOVERER, ZBX_IPC_DISCOVERER_USAGE_STATS,
			SEC_PER_MIN, NULL, 0, &result, error))
	{
		return FAIL;
	}

	discovery_unpack_usage_stats(usage, count, result);
	zbx_free(result);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Purpose: pack diagnostic statistics data into a single buffer that can be  *
 *          used in IPC                                                       *
 * Parameters: data    - [OUT] memory buffer for packed data                  *
 *             usage   - [IN] the worker usage statistics                     *
 *             count   - [IN]                                                 *
 *                                                                            *
 ******************************************************************************/
zbx_uint32_t	zbx_discovery_pack_usage_stats(unsigned char **data, const zbx_vector_dbl_t *usage, int count)
{
	unsigned char	*ptr;
	zbx_uint32_t	data_len;
	int		i;

	data_len = (zbx_uint32_t)((unsigned int)usage->values_num * sizeof(double) + sizeof(int) + sizeof(int));

	ptr = *data = (unsigned char *)zbx_malloc(NULL, data_len);

	ptr += zbx_serialize_value(ptr, usage->values_num);

	for (i = 0; i < usage->values_num; i++)
		ptr += zbx_serialize_value(ptr, usage->values[i]);

	(void)zbx_serialize_value(ptr, count);

	return data_len;
}

void zbx_discovery_stats_ext_get(struct zbx_json *json, const void *arg)
{
	zbx_uint64_t	size;

	ZBX_UNUSED(arg);

	/* zabbix[discovery_queue] */
	if (SUCCEED == zbx_discovery_get_queue_size(&size, NULL))
		zbx_json_adduint64(json, "discovery_queue", size);
}

/******************************************************************************
 *                                                                            *
 * Purpose: get discovery worker usage statistics                             *
 *                                                                            *
 ******************************************************************************/
void	zbx_discovery_get_worker_info(zbx_process_info_t *info)
{
	zbx_vector_dbl_t	usage;
	char			*error = NULL;
	int			i;

	zbx_vector_dbl_create(&usage);

	memset(info, 0, sizeof(zbx_process_info_t));

	if (SUCCEED != zbx_discovery_get_usage_stats(&usage, &info->count, &error))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot get discovery usage statistics: %s", error);
		zbx_free(error);
		goto out;
	}

	if (0 == usage.values_num)
		goto out;

	info->busy_min = info->busy_max = info->busy_avg = usage.values[0];

	for (i = 1; i < usage.values_num; i++)
	{
		if (usage.values[i] < info->busy_min)
			info->busy_min = usage.values[i];

		if (usage.values[i] > info->busy_max)
			info->busy_max = usage.values[i];

		info->busy_avg += usage.values[i];
	}

	info->busy_avg /= (double)usage.values_num;

	info->idle_min = 100.0 - info->busy_min;
	info->idle_max = 100.0 - info->busy_max;
	info->idle_avg = 100.0 - info->busy_avg;
	info->count = usage.values_num;
out:
	zbx_vector_dbl_destroy(&usage);
}

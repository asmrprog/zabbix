<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
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


/**
 * Constant values used in schema.
 */
class CXmlConstantValue {

	// Values.
	const ENABLED = 0;
	const DISABLED = 1;

	const XML_DEFAULT = IPMI_AUTHTYPE_DEFAULT;
	const NONE = IPMI_AUTHTYPE_NONE;
	const MD2 = IPMI_AUTHTYPE_MD2;
	const MD5 = IPMI_AUTHTYPE_MD5;
	const STRAIGHT = IPMI_AUTHTYPE_STRAIGHT;
	const OEM = IPMI_AUTHTYPE_OEM;
	const RMCP_PLUS = IPMI_AUTHTYPE_RMCP_PLUS;

	const CALLBACK = IPMI_PRIVILEGE_CALLBACK;
	const USER = IPMI_PRIVILEGE_USER;
	const OPERATOR = IPMI_PRIVILEGE_OPERATOR;
	const ADMIN = IPMI_PRIVILEGE_ADMIN;
	// const OEM = IPMI_PRIVILEGE_OEM; // Duplicate.

	const NO_ENCRYPTION = HOST_ENCRYPTION_NONE;
	const TLS_PSK = HOST_ENCRYPTION_PSK;
	const TLS_CERTIFICATE = HOST_ENCRYPTION_CERTIFICATE;

	const NO = 0;
	const YES = 1;

	const ZABBIX = INTERFACE_TYPE_AGENT;
	const SNMP = INTERFACE_TYPE_SNMP;
	const IPMI = INTERFACE_TYPE_IPMI;
	const JMX = INTERFACE_TYPE_JMX;

	const INV_MODE_DISABLED = HOST_INVENTORY_DISABLED; // Duplicate.
	const INV_MODE_MANUAL = HOST_INVENTORY_MANUAL;
	const INV_MODE_AUTOMATIC = HOST_INVENTORY_AUTOMATIC;

	const TRIGGER_EXPRESSION = ZBX_RECOVERY_MODE_EXPRESSION;
	const TRIGGER_RECOVERY_EXPRESSION = ZBX_RECOVERY_MODE_RECOVERY_EXPRESSION;
	const TRIGGER_NONE = ZBX_RECOVERY_MODE_NONE; // Duplicate.

	const TRIGGER_DISABLED = ZBX_TRIGGER_CORRELATION_NONE; // Duplicate.
	const TRIGGER_TAG_VALUE = ZBX_TRIGGER_CORRELATION_TAG;

	const NOT_CLASSIFIED = TRIGGER_SEVERITY_NOT_CLASSIFIED;
	const INFO = TRIGGER_SEVERITY_INFORMATION;
	const WARNING = TRIGGER_SEVERITY_WARNING;
	const AVERAGE = TRIGGER_SEVERITY_AVERAGE;
	const HIGH = TRIGGER_SEVERITY_HIGH;
	const DISASTER = TRIGGER_SEVERITY_DISASTER;

	const SINGLE = TRIGGER_MULT_EVENT_DISABLED;
	const MULTIPLE = TRIGGER_MULT_EVENT_ENABLED;

	const CALCULATED = GRAPH_YAXIS_TYPE_CALCULATED;
	const FIXED = GRAPH_YAXIS_TYPE_FIXED;
	const ITEM = GRAPH_YAXIS_TYPE_ITEM_VALUE;

	const NORMAL = GRAPH_TYPE_NORMAL;
	const STACKED = GRAPH_TYPE_STACKED;
	const PIE = GRAPH_TYPE_PIE;
	const EXPLODED = GRAPH_TYPE_EXPLODED;

	const SINGLE_LINE = GRAPH_ITEM_DRAWTYPE_LINE;
	const FILLED_REGION = GRAPH_ITEM_DRAWTYPE_FILLED_REGION;
	const BOLD_LINE = GRAPH_ITEM_DRAWTYPE_BOLD_LINE;
	const DOTTED_LINE = GRAPH_ITEM_DRAWTYPE_DOT;
	const DASHED_LINE = GRAPH_ITEM_DRAWTYPE_DASHED_LINE;
	const GRADIENT_LINE = GRAPH_ITEM_DRAWTYPE_GRADIENT_LINE;

	const LEFT = GRAPH_YAXIS_SIDE_LEFT;
	const RIGHT = GRAPH_YAXIS_SIDE_RIGHT;

	const MIN = CALC_FNC_MIN;
	const AVG = CALC_FNC_AVG;
	const MAX = CALC_FNC_MAX;
	const ALL = CALC_FNC_ALL;
	const LAST = CALC_FNC_LST;

	const SIMPLE = GRAPH_ITEM_SIMPLE;
	const GRAPH_SUM = GRAPH_ITEM_SUM;

	const PASSWORD = ITEM_AUTHTYPE_PASSWORD;
	const PUBLIC_KEY = ITEM_AUTHTYPE_PUBLICKEY;

	// const NONE = HTTPTEST_AUTH_NONE; // Duplicate.
	const BASIC = HTTPTEST_AUTH_BASIC;
	const NTLM = HTTPTEST_AUTH_NTLM;
	const KERBEROS = HTTPTEST_AUTH_KERBEROS;

	const ALIAS = 4;
	const ASSET_TAG = 11;
	const CHASSIS = 28;
	const CONTACT = 23;
	const CONTRACT_NUMBER = 32;
	const DATE_HW_DECOMM = 47;
	const DATE_HW_EXPIRY = 46;
	const DATE_HW_INSTALL = 45;
	const DATE_HW_PURCHASE = 44;
	const DEPLOYMENT_STATUS = 34;
	const HARDWARE = 14;
	const HARDWARE_FULL = 15;
	const HOST_NETMASK = 39;
	const HOST_NETWORKS = 38;
	const HOST_ROUTER = 40;
	const HW_ARCH = 30;
	const INSTALLER_NAME = 33;
	const LOCATION = 24;
	const LOCATION_LAT = 25;
	const LOCATION_LON = 26;
	const MACADDRESS_A = 12;
	const MACADDRESS_B = 13;
	const MODEL = 29;
	const NAME = 3;
	const NOTES = 27;
	const OOB_IP = 41;
	const OOB_NETMASK = 42;
	const OOB_ROUTER = 43;
	const OS = 5;
	const OS_FULL = 6;
	const OS_SHORT = 7;
	const POC_1_CELL = 61;
	const POC_1_EMAIL = 58;
	const POC_1_NAME = 57;
	const POC_1_NOTES = 63;
	const POC_1_PHONE_A = 59;
	const POC_1_PHONE_B = 60;
	const POC_1_SCREEN = 62;
	const POC_2_CELL = 68;
	const POC_2_EMAIL = 65;
	const POC_2_NAME = 64;
	const POC_2_NOTES = 70;
	const POC_2_PHONE_A = 66;
	const POC_2_PHONE_B = 67;
	const POC_2_SCREEN = 69;
	const SERIALNO_A = 8;
	const SERIALNO_B = 9;
	const SITE_ADDRESS_A = 48;
	const SITE_ADDRESS_B = 49;
	const SITE_ADDRESS_C = 50;
	const SITE_CITY = 51;
	const SITE_COUNTRY = 53;
	const SITE_NOTES = 56;
	const SITE_RACK = 55;
	const SITE_STATE = 52;
	const SITE_ZIP = 54;
	const SOFTWARE = 16;
	const SOFTWARE_APP_A = 18;
	const SOFTWARE_APP_B = 19;
	const SOFTWARE_APP_C = 20;
	const SOFTWARE_APP_D = 21;
	const SOFTWARE_APP_E = 22;
	const SOFTWARE_FULL = 17;
	const TAG = 10;
	const TYPE = 1;
	const TYPE_FULL = 2;
	const URL_A = 35;
	const URL_B = 36;
	const URL_C = 37;
	const VENDOR = 31;

	const RAW = ZBX_POSTTYPE_RAW;
	const JSON = ZBX_POSTTYPE_JSON;
	const XML = ZBX_POSTTYPE_XML;

	const GET = HTTPCHECK_REQUEST_GET;
	const POST = HTTPCHECK_REQUEST_POST;
	const PUT = HTTPCHECK_REQUEST_PUT;
	const HEAD = HTTPCHECK_REQUEST_HEAD;

	const BODY = HTTPTEST_STEP_RETRIEVE_MODE_CONTENT;
	const HEADERS = HTTPTEST_STEP_RETRIEVE_MODE_HEADERS;
	const BOTH = HTTPTEST_STEP_RETRIEVE_MODE_BOTH;

	const SNMPV3_MD5 = ITEM_AUTHPROTOCOL_MD5;
	const SNMPV3_SHA = ITEM_AUTHPROTOCOL_SHA;

	const DES = ITEM_PRIVPROTOCOL_DES;
	const AES = ITEM_PRIVPROTOCOL_AES;

	const NOAUTHNOPRIV = ITEM_SNMPV3_SECURITYLEVEL_NOAUTHNOPRIV;
	const AUTHNOPRIV = ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV;
	const AUTHPRIV = ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV;

	const ITEM_TYPE_ZABBIX_PASSIVE = ITEM_TYPE_ZABBIX;
	const ITEM_TYPE_SNMPV1 = ITEM_TYPE_SNMPV1;
	const ITEM_TYPE_TRAP = ITEM_TYPE_TRAPPER;
	const ITEM_TYPE_SIMPLE = ITEM_TYPE_SIMPLE;
	const ITEM_TYPE_SNMPV2 = ITEM_TYPE_SNMPV2C;
	const ITEM_TYPE_INTERNAL = ITEM_TYPE_INTERNAL;
	const ITEM_TYPE_SNMPV3 = ITEM_TYPE_SNMPV3;
	const ITEM_TYPE_ZABBIX_ACTIVE = ITEM_TYPE_ZABBIX_ACTIVE;
	const ITEM_TYPE_AGGREGATE = ITEM_TYPE_AGGREGATE;
	const ITEM_TYPE_EXTERNAL = ITEM_TYPE_EXTERNAL;
	const ITEM_TYPE_ODBC = ITEM_TYPE_DB_MONITOR;
	const ITEM_TYPE_IPMI = ITEM_TYPE_IPMI;
	const ITEM_TYPE_SSH = ITEM_TYPE_SSH;
	const ITEM_TYPE_TELNET = ITEM_TYPE_TELNET;
	const ITEM_TYPE_CALCULATED = ITEM_TYPE_CALCULATED;
	const ITEM_TYPE_JMX = ITEM_TYPE_JMX;
	const ITEM_TYPE_SNMP_TRAP = ITEM_TYPE_SNMPTRAP;
	const ITEM_TYPE_DEPENDENT = ITEM_TYPE_DEPENDENT;
	const ITEM_TYPE_HTTP_AGENT = ITEM_TYPE_HTTPAGENT;
	const ITEM_TYPE_SNMP = ITEM_TYPE_SNMP;

	const SNMP_V1 = SNMP_V1;
	const SNMP_V2 = SNMP_V2C;
	const SNMP_V3 = SNMP_V3;

	const FLOAT = ITEM_VALUE_TYPE_FLOAT;
	const CHAR = ITEM_VALUE_TYPE_STR;
	const LOG = ITEM_VALUE_TYPE_LOG;
	const UNSIGNED = ITEM_VALUE_TYPE_UINT64;
	const TEXT = ITEM_VALUE_TYPE_TEXT;

	const ORIGINAL_ERROR = ZBX_PREPROC_FAIL_DEFAULT;
	const DISCARD_VALUE = ZBX_PREPROC_FAIL_DISCARD_VALUE;
	const CUSTOM_VALUE = ZBX_PREPROC_FAIL_SET_VALUE;
	const CUSTOM_ERROR = ZBX_PREPROC_FAIL_SET_ERROR;

	const MULTIPLIER = ZBX_PREPROC_MULTIPLIER;
	const RTRIM = ZBX_PREPROC_RTRIM;
	const LTRIM = ZBX_PREPROC_LTRIM;
	const TRIM = ZBX_PREPROC_TRIM;
	const REGEX = ZBX_PREPROC_REGSUB;
	const BOOL_TO_DECIMAL = ZBX_PREPROC_BOOL2DEC;
	const OCTAL_TO_DECIMAL = ZBX_PREPROC_OCT2DEC;
	const HEX_TO_DECIMAL = ZBX_PREPROC_HEX2DEC;
	const SIMPLE_CHANGE = ZBX_PREPROC_DELTA_VALUE;
	const CHANGE_PER_SECOND = ZBX_PREPROC_DELTA_SPEED;
	const XMLPATH = ZBX_PREPROC_XPATH;
	const JSONPATH = ZBX_PREPROC_JSONPATH;
	const IN_RANGE = ZBX_PREPROC_VALIDATE_RANGE;
	const MATCHES_REGEX = ZBX_PREPROC_VALIDATE_REGEX;
	const NOT_MATCHES_REGEX = ZBX_PREPROC_VALIDATE_NOT_REGEX;
	const CHECK_JSON_ERROR = ZBX_PREPROC_ERROR_FIELD_JSON;
	const CHECK_XML_ERROR = ZBX_PREPROC_ERROR_FIELD_XML;
	const CHECK_REGEX_ERROR = ZBX_PREPROC_ERROR_FIELD_REGEX;
	const DISCARD_UNCHANGED = ZBX_PREPROC_THROTTLE_VALUE;
	const DISCARD_UNCHANGED_HEARTBEAT = ZBX_PREPROC_THROTTLE_TIMED_VALUE;
	const JAVASCRIPT = ZBX_PREPROC_SCRIPT;
	const PROMETHEUS_PATTERN = ZBX_PREPROC_PROMETHEUS_PATTERN;
	const PROMETHEUS_TO_JSON = ZBX_PREPROC_PROMETHEUS_TO_JSON;
	const CSV_TO_JSON = ZBX_PREPROC_CSV_TO_JSON;

	const AND_OR = CONDITION_EVAL_TYPE_AND_OR;
	const XML_AND = CONDITION_EVAL_TYPE_AND;
	const XML_OR = CONDITION_EVAL_TYPE_OR;
	const FORMULA = CONDITION_EVAL_TYPE_EXPRESSION;

	const CONDITION_MATCHES_REGEX = CONDITION_OPERATOR_REGEXP; // Duplicate.
	const CONDITION_NOT_MATCHES_REGEX = CONDITION_OPERATOR_NOT_REGEXP; // Duplicate.

	const OUTPUT_FORMAT_RAW = HTTPCHECK_STORE_RAW;
	const OUTPUT_FORMAT_JSON = HTTPCHECK_STORE_JSON;

	const MEDIA_TYPE_EMAIL = MEDIA_TYPE_EMAIL;
	const MEDIA_TYPE_SCRIPT = MEDIA_TYPE_EXEC;
	const MEDIA_TYPE_SMS = MEDIA_TYPE_SMS;
	const MEDIA_TYPE_WEBHOOK = MEDIA_TYPE_WEBHOOK;

	const STARTTLS = SMTP_CONNECTION_SECURITY_STARTTLS;
	const SSL_OR_TLS = SMTP_CONNECTION_SECURITY_SSL_TLS;

	const SMTP_AUTHENTICATION_NONE = SMTP_AUTHENTICATION_NONE;
	const SMTP_AUTHENTICATION_PASSWORD = SMTP_AUTHENTICATION_NORMAL;

	const CONTENT_TYPE_TEXT = SMTP_MESSAGE_FORMAT_PLAIN_TEXT;
	const CONTENT_TYPE_HTML = SMTP_MESSAGE_FORMAT_HTML;

	const EVENT_SOURCE_TRIGGERS = EVENT_SOURCE_TRIGGERS;
	const EVENT_SOURCE_DISCOVERY = EVENT_SOURCE_DISCOVERY;
	const EVENT_SOURCE_AUTOREGISTRATION = EVENT_SOURCE_AUTOREGISTRATION;
	const EVENT_SOURCE_INTERNAL = EVENT_SOURCE_INTERNAL;

	const OPERATION_MODE_PROBLEM = ACTION_OPERATION;
	const OPERATION_MODE_RECOVERY = ACTION_RECOVERY_OPERATION;
	const OPERATION_MODE_UPDATE = ACTION_ACKNOWLEDGE_OPERATION;

	const MACRO_TYPE_TEXT = ZBX_MACRO_TYPE_TEXT;
	const MACRO_TYPE_SECRET = ZBX_MACRO_TYPE_SECRET;
}

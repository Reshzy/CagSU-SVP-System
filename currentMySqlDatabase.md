127.0.0.1/information_schema/columns/ http://localhost/phpmyadmin/index.php?route=/table/sql&db=cagsu_svp_system&table=bac_meeting_attendees

Showing rows 0 - 115 (116 total, Query took 0.0092 seconds.)

SELECT table_name, column_name, data_type, is_nullable, column_key, column_default
FROM information_schema.columns
WHERE table_schema = 'cagsu_svp_system';

table_name column_name data_type is_nullable column_key column_default
bac_meetings id bigint NO PRI NULL
bac_meetings purchase_request_id bigint YES MUL NULL
bac_meetings meeting_datetime datetime NO NULL
bac_meetings location varchar YES NULL
bac_meetings status varchar NO 'scheduled'
bac_meetings title varchar YES NULL
bac_meetings agenda text YES NULL
bac_meetings minutes text YES NULL
bac_meetings created_by bigint NO MUL NULL
bac_meetings created_at timestamp YES NULL
bac_meetings updated_at timestamp YES NULL
bac_meeting_attendees id bigint NO PRI NULL
bac_meeting_attendees bac_meeting_id bigint NO MUL NULL
bac_meeting_attendees user_id bigint NO MUL NULL
bac_meeting_attendees role_at_meeting varchar YES NULL
bac_meeting_attendees attended tinyint NO 0
bac_meeting_attendees remarks text YES NULL
bac_meeting_attendees created_at timestamp YES NULL
bac_meeting_attendees updated_at timestamp YES NULL
cache key varchar NO PRI NULL
cache value mediumtext NO NULL
cache expiration int NO NULL
cache_locks key varchar NO PRI NULL
cache_locks owner varchar NO NULL
cache_locks expiration int NO NULL
departments id bigint NO PRI NULL
departments created_at timestamp YES NULL
departments updated_at timestamp YES NULL
documents id bigint NO PRI NULL
documents created_at timestamp YES NULL
documents updated_at timestamp YES NULL
failed_jobs id bigint NO PRI NULL
failed_jobs uuid varchar NO UNI NULL
failed_jobs connection text NO NULL
failed_jobs queue text NO NULL
failed_jobs payload longtext NO NULL
failed_jobs exception longtext NO NULL
failed_jobs failed_at timestamp NO current_timestamp()
jobs id bigint NO PRI NULL
jobs queue varchar NO MUL NULL
jobs payload longtext NO NULL
jobs attempts tinyint NO NULL
jobs reserved_at int YES NULL
jobs available_at int NO NULL
jobs created_at int NO NULL
job_batches id varchar NO PRI NULL
job_batches name varchar NO NULL
job_batches total_jobs int NO NULL
job_batches pending_jobs int NO NULL
job_batches failed_jobs int NO NULL
job_batches failed_job_ids longtext NO NULL
job_batches options mediumtext YES NULL
job_batches cancelled_at int YES NULL
job_batches created_at int NO NULL
job_batches finished_at int YES NULL
migrations id int NO PRI NULL
migrations migration varchar NO NULL
migrations batch int NO NULL
model_has_permissions permission_id bigint NO PRI NULL
model_has_permissions model_type varchar NO PRI NULL
model_has_permissions model_id bigint NO PRI NULL
model_has_roles role_id bigint NO PRI NULL
model_has_roles model_type varchar NO PRI NULL
model_has_roles model_id bigint NO PRI NULL
password_reset_tokens email varchar NO PRI NULL
password_reset_tokens token varchar NO NULL
password_reset_tokens created_at timestamp YES NULL
permissions id bigint NO PRI NULL
permissions name varchar NO MUL NULL
permissions guard_name varchar NO NULL
permissions created_at timestamp YES NULL
permissions updated_at timestamp YES NULL
purchase_orders id bigint NO PRI NULL
purchase_orders created_at timestamp YES NULL
purchase_orders updated_at timestamp YES NULL
purchase_requests id bigint NO PRI NULL
purchase_requests created_at timestamp YES NULL
purchase_requests updated_at timestamp YES NULL
purchase_request_items id bigint NO PRI NULL
purchase_request_items created_at timestamp YES NULL
purchase_request_items updated_at timestamp YES NULL
quotations id bigint NO PRI NULL
quotations created_at timestamp YES NULL
quotations updated_at timestamp YES NULL
roles id bigint NO PRI NULL
roles name varchar NO MUL NULL
roles guard_name varchar NO NULL
roles created_at timestamp YES NULL
roles updated_at timestamp YES NULL
role_has_permissions permission_id bigint NO PRI NULL
role_has_permissions role_id bigint NO PRI NULL
sessions id varchar NO PRI NULL
sessions user_id bigint YES MUL NULL
sessions ip_address varchar YES NULL
sessions user_agent text YES NULL
sessions payload longtext NO NULL
sessions last_activity int NO MUL NULL
suppliers id bigint NO PRI NULL
suppliers created_at timestamp YES NULL
suppliers updated_at timestamp YES NULL

users id bigint NO PRI NULL
users name varchar NO NULL
users email varchar NO UNI NULL
users email_verified_at timestamp YES NULL
users password varchar NO NULL
users department_id bigint YES MUL NULL
users employee_id varchar YES UNI NULL
users position varchar YES NULL
users phone varchar YES NULL
users is_active tinyint NO 1
users remember_token varchar YES NULL
users created_at timestamp YES NULL
users updated_at timestamp YES NULL
workflow_approvals id bigint NO PRI NULL
workflow_approvals created_at timestamp YES NULL
workflow_approvals updated_at timestamp YES NULL

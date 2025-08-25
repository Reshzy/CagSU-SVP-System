127.0.0.1/information_schema/columns/ http://localhost/phpmyadmin/index.php?route=/database/sql&db=cagsu_svp_system

Showing rows 0 - 306 (307 total, Query took 0.0147 seconds.)

SELECT table_name, column_name, data_type, is_nullable, column_key, column_default FROM information_schema.columns WHERE table_schema = 'cagsu_svp_system';

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
departments name varchar NO UNI NULL
departments code varchar NO UNI NULL
departments description text YES NULL
departments head_name varchar YES NULL
departments contact_email varchar YES NULL
departments contact_phone varchar YES NULL
departments is_active tinyint NO 1
departments created_at timestamp YES NULL
departments updated_at timestamp YES NULL
disbursement_vouchers id bigint NO PRI NULL
disbursement_vouchers voucher_number varchar NO UNI NULL
disbursement_vouchers purchase_order_id bigint NO MUL NULL
disbursement_vouchers supplier_id bigint NO MUL NULL
disbursement_vouchers amount decimal NO NULL
disbursement_vouchers voucher_date date NO NULL
disbursement_vouchers status enum NO MUL 'draft'
disbursement_vouchers prepared_by bigint NO MUL NULL
disbursement_vouchers approved_by bigint YES MUL NULL
disbursement_vouchers approved_at timestamp YES NULL
disbursement_vouchers released_at timestamp YES NULL
disbursement_vouchers paid_at timestamp YES NULL
disbursement_vouchers remarks text YES NULL
disbursement_vouchers created_at timestamp YES NULL
disbursement_vouchers updated_at timestamp YES NULL
documents id bigint NO PRI NULL
documents document_number varchar NO UNI NULL
documents documentable_type varchar NO MUL NULL
documents documentable_id bigint NO NULL
documents document_type enum NO MUL NULL
documents title varchar NO NULL
documents description text YES NULL
documents file_name varchar NO NULL
documents file_path varchar NO NULL
documents file_extension varchar NO NULL
documents file_size bigint NO NULL
documents mime_type varchar NO NULL
documents version int NO 1
documents previous_version_id bigint YES MUL NULL
documents is_current_version tinyint NO 1
documents uploaded_by bigint NO MUL NULL
documents is_public tinyint NO 0
documents visible_to_roles longtext YES NULL
documents status enum NO 'draft'
documents created_at timestamp YES NULL
documents updated_at timestamp YES NULL
failed_jobs id bigint NO PRI NULL
failed_jobs uuid varchar NO UNI NULL
failed_jobs connection text NO NULL
failed_jobs queue text NO NULL
failed_jobs payload longtext NO NULL
failed_jobs exception longtext NO NULL
failed_jobs failed_at timestamp NO current_timestamp()
inventory_receipts id bigint NO PRI NULL
inventory_receipts purchase_order_id bigint NO MUL NULL
inventory_receipts received_date date NO NULL
inventory_receipts reference_no varchar YES NULL
inventory_receipts status enum NO 'draft'
inventory_receipts notes text YES NULL
inventory_receipts received_by bigint NO MUL NULL
inventory_receipts created_at timestamp YES NULL
inventory_receipts updated_at timestamp YES NULL
inventory_receipt_items id bigint NO PRI NULL
inventory_receipt_items inventory_receipt_id bigint NO MUL NULL
inventory_receipt_items description varchar NO NULL
inventory_receipt_items unit_of_measure varchar YES NULL
inventory_receipt_items quantity decimal NO 0.00
inventory_receipt_items unit_price decimal YES NULL
inventory_receipt_items total_price decimal YES NULL
inventory_receipt_items created_at timestamp YES NULL
inventory_receipt_items updated_at timestamp YES NULL
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
purchase_orders po_number varchar NO UNI NULL
purchase_orders purchase_request_id bigint NO MUL NULL
purchase_orders supplier_id bigint NO MUL NULL
purchase_orders quotation_id bigint NO MUL NULL
purchase_orders po_date date NO NULL
purchase_orders total_amount decimal NO NULL
purchase_orders delivery_address text NO NULL
purchase_orders delivery_date_required date NO NULL
purchase_orders terms_and_conditions text NO NULL
purchase_orders special_instructions text YES NULL
purchase_orders status enum NO MUL 'draft'
purchase_orders approved_by bigint YES MUL NULL
purchase_orders approved_at timestamp YES NULL
purchase_orders sent_to_supplier_at timestamp YES NULL
purchase_orders acknowledged_at timestamp YES NULL
purchase_orders actual_delivery_date date YES NULL
purchase_orders delivery_notes text YES NULL
purchase_orders delivery_complete tinyint NO 0
purchase_orders created_at timestamp YES NULL
purchase_orders updated_at timestamp YES NULL
purchase_requests id bigint NO PRI NULL
purchase_requests pr_number varchar NO UNI NULL
purchase_requests requester_id bigint NO MUL NULL
purchase_requests department_id bigint NO MUL NULL
purchase_requests purpose varchar NO NULL
purchase_requests justification text YES NULL
purchase_requests date_needed date NO NULL
purchase_requests priority enum NO 'medium'
purchase_requests estimated_total decimal NO NULL
purchase_requests funding_source varchar YES NULL
purchase_requests budget_code varchar YES NULL
purchase_requests procurement_type enum NO NULL
purchase_requests procurement_method enum YES NULL
purchase_requests status enum NO MUL 'draft'
purchase_requests current_handler_id bigint YES MUL NULL
purchase_requests current_step_notes text YES NULL
purchase_requests status_updated_at timestamp YES NULL
purchase_requests has_ppmp tinyint NO 0
purchase_requests ppmp_reference varchar YES NULL
purchase_requests submitted_at timestamp YES NULL
purchase_requests approved_at timestamp YES NULL
purchase_requests completed_at timestamp YES NULL
purchase_requests total_processing_days int YES NULL
purchase_requests rejection_reason text YES NULL
purchase_requests rejected_by bigint YES MUL NULL
purchase_requests rejected_at timestamp YES NULL
purchase_requests created_at timestamp YES NULL
purchase_requests updated_at timestamp YES NULL
purchase_request_items id bigint NO PRI NULL
purchase_request_items purchase_request_id bigint NO MUL NULL
purchase_request_items item_code varchar YES NULL
purchase_request_items item_name varchar NO NULL
purchase_request_items detailed_specifications text NO NULL
purchase_request_items unit_of_measure varchar NO NULL
purchase_request_items quantity_requested int NO NULL
purchase_request_items estimated_unit_cost decimal NO NULL
purchase_request_items estimated_total_cost decimal NO NULL
purchase_request_items item_category enum NO MUL NULL
purchase_request_items special_requirements text YES NULL
purchase_request_items needed_by_date date YES NULL
purchase_request_items is_available_locally tinyint NO 1
purchase_request_items budget_line_item varchar YES NULL
purchase_request_items approved_budget decimal YES NULL
purchase_request_items item_status enum NO 'pending'
purchase_request_items rejection_reason text YES NULL
purchase_request_items modification_notes text YES NULL
purchase_request_items awarded_unit_price decimal YES NULL
purchase_request_items awarded_total_price decimal YES NULL
purchase_request_items awarded_supplier_id bigint YES MUL NULL

purchase_request_items created_at timestamp YES NULL
purchase_request_items updated_at timestamp YES NULL
quotations id bigint NO PRI NULL
quotations quotation_number varchar NO UNI NULL
quotations purchase_request_id bigint NO MUL NULL
quotations supplier_id bigint NO MUL NULL
quotations quotation_date date NO NULL
quotations validity_date date NO NULL
quotations total_amount decimal NO NULL
quotations terms_and_conditions text YES NULL
quotations delivery_days int YES NULL
quotations delivery_terms text YES NULL
quotations payment_terms text YES NULL
quotations bac_status enum NO 'pending_evaluation'
quotations technical_score decimal YES NULL
quotations financial_score decimal YES NULL
quotations total_score decimal YES NULL
quotations bac_remarks text YES NULL
quotations evaluated_by bigint YES MUL NULL
quotations evaluated_at timestamp YES NULL
quotations is_winning_bid tinyint NO 0
quotations award_justification text YES NULL
quotations awarded_at timestamp YES NULL
quotations quotation_file_path varchar YES NULL
quotations supporting_documents varchar YES NULL
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
suppliers supplier_code varchar NO UNI NULL
suppliers business_name varchar NO NULL
suppliers trade_name varchar YES NULL
suppliers business_type enum NO NULL
suppliers contact_person varchar NO NULL
suppliers position varchar YES NULL
suppliers email varchar NO UNI NULL
suppliers phone varchar NO NULL
suppliers mobile varchar YES NULL
suppliers fax varchar YES NULL
suppliers address text NO NULL
suppliers city varchar NO NULL
suppliers province varchar NO NULL
suppliers postal_code varchar YES NULL
suppliers tin varchar YES NULL
suppliers business_permit varchar YES NULL
suppliers permit_expiry date YES NULL
suppliers philgeps_registration varchar YES NULL
suppliers status enum NO MUL 'pending_verification'
suppliers specialization text YES NULL
suppliers performance_rating decimal NO 0.00
suppliers total_contracts int NO 0
suppliers total_contract_value decimal NO 0.00
suppliers password varchar YES NULL
suppliers email_verified_at timestamp YES NULL
suppliers remember_token varchar YES NULL
suppliers last_login_at timestamp YES NULL
suppliers created_at timestamp YES NULL
suppliers updated_at timestamp YES NULL
supplier_messages id bigint NO PRI NULL
supplier_messages purchase_request_id bigint YES MUL NULL
supplier_messages supplier_id bigint YES MUL NULL
supplier_messages supplier_name varchar YES NULL
supplier_messages supplier_email varchar NO MUL NULL
supplier_messages subject varchar NO NULL
supplier_messages message_body text NO NULL
supplier_messages status enum NO 'new'
supplier_messages created_at timestamp YES NULL
supplier_messages updated_at timestamp YES NULL
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
workflow_approvals purchase_request_id bigint NO MUL NULL
workflow_approvals step_name enum NO NULL
workflow_approvals step_order int NO NULL
workflow_approvals approver_id bigint NO MUL NULL
workflow_approvals approved_by bigint YES MUL NULL
workflow_approvals status enum NO 'pending'

workflow_approvals comments text YES NULL
workflow_approvals rejection_reason text YES NULL
workflow_approvals assigned_at timestamp NO current_timestamp()
workflow_approvals responded_at timestamp YES NULL
workflow_approvals days_to_respond int YES NULL
workflow_approvals created_at timestamp YES NULL
workflow_approvals updated_at timestamp YES NULL

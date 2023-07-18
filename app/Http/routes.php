<?php
	
	use App\Http\Controllers\AuditsController;
	use App\Http\Controllers\Files\FileController;
	use App\Physician;
	use Illuminate\Support\Facades\Route;
	use App\Group;
	use App\Hospital;
	use App\ApprovalManagerInfo;
	use App\RegionHospitals;
	use App\Models\PracticeUser;
	
	$superuser = GROUP::SUPER_USER;
	$superhospitaluser = GROUP::SUPER_HOSPITAL_USER;
	$hospitaluser = GROUP::HOSPITAL_ADMIN;
	$practicemanager = GROUP::PRACTICE_MANAGER;
	$physicians = GROUP::PHYSICIANS;
	$healthsystemuser = GROUP::HEALTH_SYSTEM_USER;
	$healthsystemregionuser = GROUP::HEALTH_SYSTEM_REGION_USER;
	$hospitalcfo = GROUP::HOSPITAL_CFO;
	
	Route::middleware(['auth', 'cognito'])->group(function () {
		Route::get('/', function () {
			return view('welcome');
		})->name('welcome');
	});
	
	Route::get('/sso/login', ["uses" => "AuthController@ssoLogin"]);

// *******************************************************************************************************************
// TRACE Authentication Routes
//
// 1. Login/Logout
// 2. Password Reminders
//
// *******************************************************************************************************************
	Route::get("/login", ["uses" => "AuthController@getLogin"])->name('auth.login');
	Route::post("/login", ["uses" => "AuthController@postLogin"])->middleware(['throttle:api']);
	Route::get("/logout", ["uses" => "AuthController@getLogout"])->name('auth.logout');
	Route::get("/password/reset", ["uses" => "PasswordController@getRemind"])->name('password.remind');
	Route::post("/password/reset", ["uses" => "PasswordController@postRemind"])->name('password.email')->middleware(['throttle:api']);
	Route::get("/password/reset/{token}", ["uses" => "PasswordController@getReset"])->name('password.reset');
	Route::post("/password/reset/{token}", ["uses" => "PasswordController@postReset"])->middleware(['throttle:api']);
	
	Route::get('/login/organization/login', ['uses' => "SSOController@getIndex"])->name('sso.email_request');
	Route::get('/login/organization/login/{email}', ['uses' => "SSOController@getIndex"])->name('sso.email_request_with_email');
	Route::post('/login/organization/login', ['uses' => "SSOController@postIndex"]);


// *******************************************************************************************************************
// TRACE Dashboard
//
// 1. Launch Page
// 2. Search
// 3. Help Center/Tickets
// 4. Mobile App
// 5. Guide/Overview
//
// *******************************************************************************************************************
	Route::get("/", ["uses" => "DashboardController@getIndex"])->name('dashboard.index');
	Route::group(['middleware' => 'auth'], function () {
		Route::post("/search", ["uses" => "SearchController@postQuery"])->name('dashboard.search');
	});
	Route::get("/admin_dashboard", ["uses" => "DashboardController@getDashboardForAdmin"])->name('dashboard.admin');
	Route::get("/getContractDetailsCount", ["uses" => "DashboardController@getContractDetailsCount"])->name('dashboard.getContractDetailsCount');
	Route::get("/getAgreementDataByAjax", ["uses" => "DashboardController@getAgreementDataByAjax"])->name('dashboard.getAgreementDataByAjax');
	Route::get("/getContractPaymentCounts", ["uses" => "DashboardController@getContractPaymentCounts"])->name('dashboard.getContractPaymentCounts');
	Route::get("/getRegionFacilities/{r_id}", ["uses" => "DashboardController@getRegionFacilities"])->name('dashboard.getRegionFacilities');
	Route::get("/getActiveContractTypesChart/{r_id}/{h_id}/{group_id}", ["uses" => "DashboardController@getActiveContractTypesChart"])->name('dashboard.getActiveContractTypesChart');
	Route::get("/getActiveContractsByType/{r_id}/{h_id}/{type_id}/{group_id}", ["uses" => "DashboardController@getActiveContractsByType"])->name('dashboard.getActiveContractsByType');
	Route::get("/getContractSpendYTDChart/{r_id}/{h_id}/{group_id}", ["uses" => "DashboardController@getContractSpendYTDChart"])->name('dashboard.getContractSpendYTDChart');
	Route::get("/getContractSpendYTD/{r_id}/{h_id}/{type_id}/{totalPaid}/{group_id}", ["uses" => "DashboardController@getContractSpendYTD"])->name('dashboard.getContractSpendYTD');
	Route::get("/getContractTypesEffectivenessChart/{r_id}/{h_id}/{group_id}", ["uses" => "DashboardController@getContractTypesEffectivenessChart"])->name('dashboard.getContractTypesEffectivenessChart');
	Route::get("/getActiveContractsTypeEffectivness/{r_id}/{h_id}/{type_id}/{group_id}", ["uses" => "DashboardController@getActiveContractsTypeEffectivness"])->name('dashboard.getActiveContractsTypeEffectivness');
	Route::get("/getContractSpendEffectivenessChart/{r_id}/{h_id}/{group_id}", ["uses" => "DashboardController@getContractSpendEffectivenessChart"])->name('dashboard.getContractSpendEffectivenessChart');
	Route::get("/getActiveContractSpendEffectivness/{r_id}/{h_id}/{type_id}/{group_id}", ["uses" => "DashboardController@getActiveContractSpendEffectivness"])->name('dashboard.getActiveContractSpendEffectivness');
	Route::get("/getContractSpendToActualChart/{r_id}/{h_id}/{group_id}", ["uses" => "DashboardController@getContractSpendToActualChart"])->name('dashboard.getContractSpendToActualChart');
	Route::get("/getContractSpendToActual/{r_id}/{h_id}/{type_id}/{totalSpend}/{group_id}", ["uses" => "DashboardController@getContractSpendToActual"])->name('dashboard.getContractSpendToActual');
	Route::get("/getContractTypesAlertsChart/{r_id}/{h_id}/{group_id}", ["uses" => "DashboardController@getContractTypesAlertsChart"])->name('dashboard.getContractTypesAlertsChart');
	Route::get("/getContractTypesAlerts/{r_id}/{h_id}/{type_id}/{payment}/{group_id}", ["uses" => "DashboardController@getContractTypesAlerts"])->name('dashboard.getContractTypesAlerts');
	Route::get("/getContractTypesByRegionChart/{group_id}", ["uses" => "DashboardController@getContractTypesByRegionChart"])->name('dashboard.getContractTypesByRegionChart');
	Route::get("/getFacilityContractCountDataByAjax/{r_id}/{group_id}", ["uses" => "DashboardController@getFacilityContractCountDataByAjax"])->name('dashboard.getFacilityContractCountDataByAjax');
	Route::get("/getFacilityActiveContracts/{h_name}/{group_id}", ["uses" => "DashboardController@getFacilityActiveContracts"])->name('dashboard.getFacilityActiveContracts');
	Route::get("/getFacilityContractSpecifyDataByAjax/{h_id}", ["uses" => "DashboardController@getFacilityContractSpecifyDataByAjax"])->name('dashboard.getFacilityContractSpecifyDataByAjax');
	Route::get("/getComplianceRejectionByPractice/{h_id}/{practice_id}", ["uses" => "DashboardController@getComplianceRejectionByPractice"])->name('dashboard.getComplianceRejectionByPractice');
	Route::get("/getComplianceRejectionByPhysician/{h_id}/{physician_id}", ["uses" => "DashboardController@getComplianceRejectionByPhysician"])->name('dashboard.getComplianceRejectionByPhysician');
	Route::get("/getComplianceRejectionRateOverall/{h_id}/{organization_id}", ["uses" => "DashboardController@getComplianceRejectionRateOverall"])->name('dashboard.getComplianceRejectionRateOverall');
	Route::get("/postHospitalsContractTotalSpendAndPaid", ["uses" => "DashboardController@postHospitalsContractTotalSpendAndPaid"]);
	Route::get("/updatePendingPaymentCountForAllHospitals", ["uses" => "ApprovalController@updatePendingPaymentCountForAllHospitals"]);
	Route::get("/updateTotalAndRejectedLogsForHospital", ["uses" => "DashboardController@updateTotalAndRejectedLogsForHospital"]);
	Route::get("/updateHospitalActions", ["uses" => "HospitalsController@updateHospitalActions"]);
	Route::get("/invoiceDashboardOnOff", ["uses" => "ApprovalController@invoiceDashboardOnOff"]);
	Route::get("/updateHospitalCustomInvoice/{id}/{ci_id}", ["uses" => "HospitalsController@updateHospitalCustomInvoice"]);
	Route::get("/approverpendinglog/{h_id}", ["uses" => "ApprovalController@approverpendinglog"]);
	Route::get("/tickets", ["uses" => "TicketsController@getIndex"])->name('tickets.index');
	Route::get("/tickets/create", ["uses" => "TicketsController@getCreate"])->name('tickets.create');
	Route::post("/tickets/create", ["uses" => "TicketsController@postCreate"]);
	Route::get("/tickets/{ticket_id}", ["uses" => "TicketsController@getShow"])->name('tickets.show');
	Route::post("/tickets/{ticket_id}", ["uses" => "TicketsController@postCreateMessage"]);
	Route::get("/tickets/{ticket_id}/edit", ["uses" => "TicketsController@getEdit"])->name('tickets.edit');
	Route::post("/tickets/{ticket_id}/edit", ["uses" => "TicketsController@postEdit"]);
	Route::get("/tickets/{ticket_id}/messages/{message_id}/edit", ["uses" => "TicketsController@getEditMessage"])->name('tickets.edit_message');
	Route::post("/tickets/{ticket_id}/messages/{message_id}/edit", ["uses" => "TicketsController@postEditMessage"]);
	Route::get("/tickets/{ticket_id}/open", ["uses" => "TicketsController@getOpen"])->name('tickets.open');
	Route::get("/tickets/{ticket_id}/close", ["uses" => "TicketsController@getClose"])->name('tickets.close');;
	Route::get("/tickets/{ticket_id}/delete", ["uses" => "TicketsController@getDelete"])->name('tickets.delete');
	Route::post("/traceapp/login", ["uses" => "TraceAppController@postLogin"])->middleware(['throttle:api']);
	Route::post("/traceapp/changepassword", ["uses" => "TraceAppController@postChangePassword"])->middleware(['throttle:api']);
	Route::post("/traceapp/savelog", ["uses" => "TraceAppController@postSaveLog"]);
	Route::post("/traceapp/deletelog", ["uses" => "TraceAppController@postDeleteLog"]);
	Route::get("/guide", function () {
		return View::make("dashboard/guide");
	});
	Route::get("/overview", function () {
		return View::make("dashboard/overview");
	});
	
	Route::get("/actions", ["uses" => "ActionsController@getIndex"])->name('actions.index');
	Route::get("/actions/create", ["uses" => "ActionsController@getCreate"])->name('actions.create');
	Route::post("/actions/create", ["uses" => "ActionsController@postCreate"]);
	Route::get("/actions/{id}/edit", ["uses" => "ActionsController@getEdit"])->name('actions.edit');
	Route::post("/actions/{id}/edit", ["uses" => "ActionsController@postEdit"]);
	Route::get("/actions/{id}/delete", ["uses" => "ActionsController@getDelete"])->name('actions.delete');
	Route::get("/contract_names", ["uses" => "ContractNamesController@getIndex"])->name('contract_names.index');
	Route::get("/contract_names/create", ["uses" => "ContractNamesController@getCreate"])->name('contract_names.create');
	Route::post("/actionsearch", ["as" => "actions.search", "uses" => "ActionsController@postQuery"]);
	Route::post("/contract_names/create", ["uses" => "ContractNamesController@postCreate"]);
	Route::get("/contract_names/{id}/edit", ["uses" => "ContractNamesController@getEdit"])->name('contract_names.edit');
	Route::post("/contract_names/{id}/edit", ["uses" => "ContractNamesController@postEdit"]);
	Route::get("/contract_names/{id}/delete", ["uses" => "ContractNamesController@getDelete"])->name('contract_names.delete');
	
	Route::get("/contract_types", ["uses" => "ContractTypesController@getIndex"])->name('contract_types.index');
	Route::get("/contract_types/create", ["uses" => "ContractTypesController@getCreate"])->name('contract_types.create');
	Route::post("/contract_types/create", ["uses" => "ContractTypesController@postCreate"]);
	Route::get("/contract_types/{id}/edit", ["uses" => "ContractTypesController@getEdit"])->name('contract_types.edit');
	Route::post("/contract_types/{id}/edit", ["uses" => "ContractTypesController@postEdit"]);
	Route::get("/contract_types/{id}/delete", ["uses" => "ContractTypesController@getDelete"])->name('contract_types.delete');
	
	Route::get("/specialties", ["uses" => "SpecialtiesController@getIndex"])->name('specialties.index');
	Route::get("/specialties/create", ["uses" => "SpecialtiesController@getCreate"])->name('specialties.create');
	Route::post("/specialties/create", ["uses" => "SpecialtiesController@postCreate"]);
	Route::get("/specialties/{id}/edit", ["uses" => "SpecialtiesController@getEdit"])->name('specialties.edit');
	Route::post("/specialties/{id}/edit", ["uses" => "SpecialtiesController@postEdit"]);
	Route::get("/specialties/{id}/delete", ["uses" => "SpecialtiesController@getDelete"])->name('specialties.delete');
	
	Route::get("/reports", ["uses" => "ReportsController@getIndex"])->name('reports.index');
	Route::post("/reports/allAgreements", ["uses" => "ReportsController@getIndexForAllAgreements"]);
	Route::post("/reports", ["uses" => "ReportsController@postIndex"]);
	Route::get("/reports/{report_id}", ["uses" => "ReportsController@getReport"])->name('reports.download');
	Route::get("/reports/{report_id}/delete", ["uses" => "ReportsController@getDelete"])->name('reports.delete');
	
	Route::get("/system_logs", ["uses" => "SystemLogsController@getIndex"])->name('system_logs.index');
	Route::get("/system_logs/{log_id}", ["uses" => "SystemLogsController@getShow"])->name('system_logs.show');
	Route::get("/system_logs/{log_id}/delete", ["uses" => "SystemLogsController@getDelete"])->name('system_logs.delete');
	
	Route::get('sso_clients', ['uses' => 'SSOClientController@getIndex'])->name('sso_clients.index');
	Route::get('sso_clients/create', ['uses' => 'SSOClientController@getCreate'])->name('sso_clients.create');
	Route::post('sso_clients/create', ['uses' => 'SSOClientController@postCreate']);
	Route::get('sso_clients/{id}', ['uses' => 'SSOClientController@getShow'])->name('sso_clients.show');
	Route::get('sso_clients/{id}/edit', ['uses' => 'SSOClientController@getEdit'])->name('sso_clients.edit');
	Route::post('sso_clients/{id}/edit', ['uses' => 'SSOClientController@postEdit']);
	Route::get('sso_clients/{id}/delete', ['uses' => 'SSOClientController@getDelete'])->name('sso_clients.delete');
	
	
	Route::get("/interface_types", ["uses" => "InterfaceTypesController@getIndex"])->name('interface_types.index');
	Route::get('hospitals/{id}/interfacedetails', ['uses' => 'HospitalsController@interfaceDetails'])->name('hospitals.interfacedetails');
	Route::post('hospitals/{id}/interfacedetails', ['uses' => 'HospitalsController@postInterfaceDetails']);
	
	Route::get('hospitals/{id}/masswelcomeemailer', ['uses' => 'HospitalsController@getMassWelcomeEmailer'])->name('hospitals.masswelcomeemailer');
	Route::post('hospitals/{id}/masswelcomeemailer', ['uses' => 'HospitalsController@postMassWelcomeEmailer']);
	
	Route::get('hospitals/{id}/dutiesmanagement', ['uses' => 'HospitalsController@getDutiesManagement'])->name('hospitals.dutiesmanagement');
	Route::post('hospitals/{id}/dutiesmanagement', ['uses' => 'HospitalsController@postDutiesManagement']);
	
	Route::get('switchuser', ['uses' => 'UserSwitchController@switchUser'])->name('userswitch.switchuser');
	Route::get('restoreuser', ['uses' => 'UserSwitchController@restoreUser'])->name('userswitch.restoreuser');
	Route::post("contracts/{c_id}/edit/{pid}/physician/{id}", ["uses" => "ContractsController@postEdit"]);
	Route::post("contracts/{id}/edit/psa", ["uses" => "ContractsController@postPsaEdit"]);
	Route::post("/UpdateCustomCategoriesActions/{contract_id}", ["uses" => "ContractsController@UpdateCustomCategoriesActions"]);
	Route::post('contracts/{id}/{p_id}/displayContractApprovers', ['uses' => 'ContractsController@updateContractApprovers']);
	Route::post('contracts/{id}/interfacedetails/{pid}', ['uses' => 'ContractsController@postInterfaceDetails']);
	Route::post('contracts/{id}/copycontract/{pid}/physician/{p_id}', ['uses' => 'ContractsController@postCopyContract']);
	Route::post('contracts/{id}/unapprovelogs/{pid}/physician/{phy_id}', ['uses' => 'ContractsController@postUnapproveLogs']);
	Route::post('contracts/{id}/paymentmanagement/{pid}/physician/{p_id}', ['uses' => 'ContractsController@postPaymentManagement']);
	Route::group(['middleware' => 'auth'], function () {
		Route::get('emailer', ['uses' => 'DashboardController@getEmailer'])->name('dashboard.emailer');
		Route::post('emailer', ['uses' => 'DashboardController@postEmailer']);
	});
	
	
	Route::group(['middleware' => 'auth'], function () {
		Route::get('hospitals/{id}/admins', ['uses' => 'HospitalsController@getAdmins'])->name('hospitals.admins');
		Route::get('hospitals', ['uses' => 'HospitalsController@getIndex'])->name('hospitals.index');
		Route::get('hospitals/create', ['uses' => 'HospitalsController@getCreate'])->name('hospitals.create');
		Route::post('hospitals/create', ['uses' => 'HospitalsController@postCreate']);
		Route::get('hospitals/{id}', ['uses' => 'HospitalsController@getShow'])->name('hospitals.show');
		Route::get('hospitals/{id}/edit', ['uses' => 'HospitalsController@getEdit'])->name('hospitals.edit');
		Route::post('hospitals/{id}/edit', ['uses' => 'HospitalsController@postEdit']);
		Route::get('hospitals/{id}/agreements', ['uses' => 'HospitalsController@getAgreements'])->name('hospitals.agreements');
		Route::get('hospitals/{id}/agreements/create', ['uses' => 'HospitalsController@getCreateAgreement'])->name('hospitals.create_agreement');
		Route::post('hospitals/{id}/agreements/create', ['uses' => 'HospitalsController@postCreateAgreement']);
		Route::get('hospitals/{id}/admins/add', ['uses' => 'HospitalsController@getAddAdmin'])->name('hospitals.add_admin');
		Route::post('hospitals/{id}/admins/add', ['uses' => 'HospitalsController@postAddAdmin']);
		Route::get('hospitals/{id}/admins/create', ['uses' => 'HospitalsController@getCreateAdmin'])->name('hospitals.create_admin');
		Route::post('hospitals/{id}/admins/create', ['uses' => 'HospitalsController@postCreateAdmin']);
		Route::get('hospitals/{id}/practices', ['uses' => 'HospitalsController@getPractices'])->name('hospitals.practices');
		Route::get('hospitals/{id}/practices/create', ['uses' => 'HospitalsController@getCreatePractice'])->name('hospitals.create_practice');
		Route::post('hospitals/{id}/practices/create', ['uses' => 'HospitalsController@postCreatePractice']);
		Route::get('hospitals/{id}/reports', ['uses' => 'HospitalsController@getReports'])->name('hospitals.reports');
		Route::post('hospitals/{id}/reports', ['uses' => 'HospitalsController@postReports']);
		Route::get('hospitals/{id}/reports/{report}', ['uses' => 'HospitalsController@getReport'])->name('hospitals.report');
		Route::get('hospitals/{id}/reports/{report}/delete', ['uses' => 'HospitalsController@getDeleteReport'])->name('hospitals.delete_report');
		Route::get('hospitals/{id}/check', ['uses' => 'HospitalsController@getInvoices'])->name('hospitals.invoices');
		Route::post('hospitals/{id}/check', ['uses' => 'HospitalsController@postInvoices']);
		Route::get('hospitals/{id}/check/{invoice}', ['uses' => 'HospitalsController@getInvoice'])->name('hospitals.invoice');
		Route::get('hospitals/{id}/check/{invoice}/delete', ['uses' => 'HospitalsController@getDeleteInvoice'])->name('hospitals.delete_invoice');
		Route::get('hospitals/{id}/archive', ['uses' => 'HospitalsController@getArchive'])->name('hospitals.archive');
		Route::get('hospitals/{id}/unarchive', ['uses' => 'HospitalsController@getUnarchive'])->name('hospitals.unarchive');
		Route::get('hospitals/{id}/delete', ['uses' => 'HospitalsController@getDelete'])->name('hospitals.delete');
		Route::get('hospitals/{id}/approvers', ['uses' => 'HospitalsController@getApprovers'])->name('hospitals.approvers');
		Route::get("hospitals/{id}/breakdown", ["uses" => "BreakdownController@getIndex"])->name('hospitals.breakdown');
		Route::post("hospitals/{id}/breakdown", ["uses" => "BreakdownController@postIndex"]);
		Route::get("hospitals/{id}/paymentSummary", ["uses" => "BreakdownController@getPaymentSummaryReport"])->name('hospitals.paymentSummary');
		Route::post("hospitals/{id}/paymentSummary", ["uses" => "BreakdownController@postPaymentSummaryReport"]);
		Route::get('hospitals/{id}/paymentStatusReports', ['uses' => 'HospitalsController@getpaymentStatusReports'])->name('hospitals.paymentStatusReports');
		Route::post('hospitals/{id}/paymentStatusReports', ['uses' => 'HospitalsController@postpaymentStatusReports']);
		Route::get('expiringContracts', ['uses' => 'HospitalsController@getExpiringContracts'])->name('hospitals.expiringContracts');
		Route::get('amendedContracts', ['uses' => 'HospitalsController@getAmendedContracts'])->name('hospitals.amendedContracts');
		Route::get('isLawsonInterfacedContracts', ['uses' => 'HospitalsController@getIsLawsonInterfacedContracts'])->name('hospitals.isLawsonInterfacedContracts');
		Route::get('hospitals/{id}/lawsonInterfaceReports', ['uses' => 'HospitalsController@getLawsonInterfaceReports'])->name('hospitals.lawsonInterfaceReports');
		Route::post('hospitals/{id}/lawsonInterfaceReports', ['uses' => 'HospitalsController@postLawsonInterfaceReports']);
		Route::get('hospitals/{id}/activeContractReports', ['uses' => 'HospitalsController@getActiveContractReports'])->name('hospitals.activeContractReports');
		Route::post('hospitals/{id}/activeContractReports', ['uses' => 'HospitalsController@postActiveContractReports']);
	});
	
	Route::get('agreements/{id}', ['uses' => 'AgreementsController@getShow'])->name('agreements.show');
	Route::get('agreements/{id}/createContract', ['uses' => 'AgreementsController@getCreateContract'])->name('agreements.createContract');
	Route::post('agreements/{id}/createContract', ['uses' => 'AgreementsController@postCreateContract']);
	Route::get('payments/{id}', ['uses' => 'AgreementsController@getShow'])->name('payments.show');
	Route::post('agreements/addPayment', ['uses' => 'AgreementsController@addPayment'])->name('agreements.addPayment');
	Route::post('agreements/getPaymentDetails', ['uses' => 'AgreementsController@getPaymentDetails'])->name('agreements.getPaymentDetails');
	Route::post('agreements/getHoursAndPaymentDetails', ['uses' => 'AgreementsController@getHoursAndPaymentDetails'])->name('agreements.getHoursAndPaymentDetails');
	Route::get('hospitals/{id}/payments', ['uses' => 'AgreementsController@getPayment'])->name('agreements.payment');
	Route::get('getPaymentDetailsForInvoiceDashboard/{h_id}', ['uses' => 'AgreementsController@getPaymentDetailsForInvoiceDashboard'])->name('agreements.getPaymentDetailsForInvoiceDashboard');
	Route::get('agreements/{id}/edit', ['uses' => 'AgreementsController@getEdit'])->name('agreements.edit');
	Route::post('agreements/{id}/edit', ['uses' => 'AgreementsController@postEdit']);
	Route::get('agreements/{id}/renew', ['uses' => 'AgreementsController@getRenew'])->name('agreements.renew');
	Route::post('agreements/{id}/renew', ['uses' => 'AgreementsController@postRenew']);
	Route::get('agreements/{id}/archive', ['uses' => 'AgreementsController@postArchive'])->name('agreements.archive');
	Route::get('agreements/{id}/delete', ['uses' => 'AgreementsController@getDelete'])->name('agreements.delete');
	Route::get('agreements/{id}/viewOnCall', ['uses' => 'AgreementsController@getShowOnCall'])->name('agreements.oncall');
	Route::post('agreements/{id}/viewOnCall', ['uses' => 'AgreementsController@postSaveOnCall']);
	Route::get('agreements/{id}/viewOnCall/getDataOnCall/{date_index}', ['uses' => 'AgreementsController@getDataOnCall']);
	Route::get('agreements/{id}/onCallEntry', ['uses' => 'AgreementsController@getShowOnCallEntry'])->name('agreements.onCallEntry');
	Route::post('agreements/{id}/onCallEntry', ['uses' => 'AgreementsController@approveOnCallLogs']);
	Route::get('agreements/{id}/onCallEntry/Delete/{log_id}', ['uses' => 'AgreementsController@deleteOnCallEntry']);
	Route::get('agreements/{id}/onCallEntry/getPreLogDate/{physician_id}', ['uses' => 'AgreementsController@getPreLogDate']);
	Route::get('agreements/{id}/copy', ['uses' => 'AgreementsController@getCopy'])->name('agreements.copy');
	Route::get('agreements/{id}/unarchive', ['uses' => 'AgreementsController@postUnarchive'])->name('agreements.unarchive');
// ---------------------------------------------------------------------------
// Practices Routes
// ---------------------------------------------------------------------------
	Route::get('practices', ['uses' => 'PracticesController@getIndex'])->name('practices.index');
	Route::get('practices/{id}', ['uses' => 'PracticesController@getShow'])->name('practices.show');
	Route::get('practices/{id}/edit', ['uses' => 'PracticesController@getEdit'])->name('practices.edit');
	Route::get('practices/{id}/agreements', ['uses' => 'PracticesController@getAgreements'])->name('practices.agreements');
	Route::get('practices/{id}/agreements/{agreement_id}', ['uses' => 'PracticesController@showPracticeAgreements'])->name('practices.agreement_show');
	Route::get('practices/{id}/agreements/show/{agreement_id}', ['uses' => 'PracticesController@showPracticeoncall'])->name('practices.oncall');
	Route::get('practices/{id}/agreements/{agreement_id}/scheduling', ['uses' => 'PracticesController@scheduling'])->name('practices.scheduling');
	Route::get('practices/{id}/agreements/{agreement_id}/scheduling/getDataOnCall/{date_index}', ['uses' => 'PracticesController@getDataOnCall']);
	Route::post('practices/{id}/agreements/{agreement_id}/scheduling', ['uses' => 'PracticesController@postSaveOnCall']);
	Route::post('practices/{id}/agreements/show/{agreement_id}', ['uses' => 'PracticesController@postSaveOnCall']);
	Route::get('practices/{id}/agreements/show/{agreement_id}/getDataOnCall/{date_index}', ['uses' => 'PracticesController@getDataOnCall']);
	Route::post('practices/{id}/edit', ['uses' => 'PracticesController@postEdit']);
	Route::get("practices/{id}/delete", ["uses" => "PracticesController@getDelete"])->name('practices.delete');
	Route::get('practices/{id}/managers', ['uses' => 'PracticesController@getManagers'])->name('practices.managers');
	Route::get('practices/{id}/managers/add', ['uses' => 'PracticesController@getAddManager'])->name('practices.add_manager');
	Route::post('practices/{id}/managers/add', ['uses' => 'PracticesController@postAddManager']);
	Route::get('practices/{id}/managers/create', ['uses' => 'PracticesController@getCreateManager'])->name('practices.create_manager');
	Route::post('practices/{id}/managers/create', ['uses' => 'PracticesController@postCreateManager']);
	Route::get('practices/{id}/managers/{manager}/delete', ['uses' => 'PracticesController@getDeleteManager'])->name('practices.delete_manager');
	Route::get('practices/{id}/physicians', ['uses' => 'PracticesController@getPhysicians'])->name('practices.physicians');
	Route::get('practices/{id}/physicians/create', ['uses' => 'PracticesController@getCreatePhysician'])->name('practices.create_physician');
	Route::post('practices/{id}/physicians/create', ['uses' => 'PracticesController@postCreatePhysician']);
	Route::get('practices/{id}/physicians/{physician}/delete', ['uses' => 'PracticesController@getDeletePhysician'])->name('practices.delete_physician');
	Route::get('practices/{id}/reports', ['uses' => 'PracticesController@getReports'])->name('practices.reports');
	Route::post('practices/{id}/reports', ['uses' => 'PracticesController@postReports']);
	Route::get('practices/{id}/reports/{report}', ['uses' => 'PracticesController@getReport'])->name('practices.report');
	Route::get('practices/{id}/reports/{report}/delete', ['uses' => 'PracticesController@getDeleteReport'])->name('practices.delete_report');
	Route::get('practices/{id}/{hospital_id}/reports', ['uses' => 'PracticesController@getManagerReports'])->name('practiceManager.reports');
	Route::post('practices/{id}/{hospital_id}/reports', ['uses' => 'PracticesController@postManagerReports']);
	Route::get('practices/{id}/managerReport/{report}', ['uses' => 'PracticesController@getManagerReport'])->name('practices.managerReport');
	Route::get('practices/{id}/{hospital_id}/breakdown', ['uses' => 'BreakdownController@getPracticeManagerReport'])->name('practiceManager.breakdown');
	Route::post('practices/{id}/{hospital_id}/breakdown', ['uses' => 'BreakdownController@postPracticeManagerReport']);
	Route::get('practices/{id}/managerReport/{report}/delete', ['uses' => 'PracticesController@getDeleteManagerReport'])->name('practices.delete_managerReport');
	Route::post('physicianApprovalEmail', ['uses' => 'PracticesController@sendPhysicianApprovalEmail']);
	Route::get('practiceManager/{id}', ['uses' => 'PracticesController@practicemanagerDashboard'])->name('practicemanager.dashboard');
	Route::get('practiceManager/{id}/getRejected/{c_id}/{h_id}', ['uses' => 'PracticesController@getRejected'])->name('practicemanager.rejected');
//One to Many by 1254
	Route::get('practices/{id}/physician/add', ['uses' => 'PracticesController@getAddPhysician'])->name('practices.add_physician');
	Route::post('practices/{id}/physician/add', ['uses' => 'PracticesController@postAddPhysician']);


//view on call schedule
	/* using contractId as a reference to agreement
	 * if agreement is passed as param, we have to send contract as well
	 */
	Route::get('practices/{id}/contracts', ['uses' => 'PracticesController@getPracticeContracts'])->name('practices.contracts');
	Route::get('practices/{id}/contract/{contract_id}', ['uses' => 'PracticesController@showPracticeContracts'])->name('practices.contracts_show');
	Route::get('contract/{id}/getOnCallScheduleData/{date_index}', ['uses' => 'PracticesController@getOnCallScheduleData']);

//on call logs
	Route::get('practices/{id}/contract/{contract_id}/logEntry', ['uses' => 'PracticesController@getShowOnCallEntry'])->name('practices.onCallEntry');
	Route::get('practices/{id}/contract/{contract_id}/physicianLogEntry', ['uses' => 'PracticesController@getShowPhysicianLogEntry'])->name('practices.physicianLogEntry');
	Route::get('practices/{id}/contract/{contract_id}/physicianPsaWrvuLogEntry', ['uses' => 'PracticesController@getShowPhysicianPsaWrvuLogEntry'])->name('practices.physicianPsaWrvuLogEntry');
	Route::post('getContracts', ['uses' => 'PracticesController@getContracts'])->name('practices.getContracts');
	Route::post('getContractPeriod', ['uses' => 'PracticesController@getContractPeriod'])->name('practices.getContractPeriod');
	Route::post('getApproveLogsViewRefresh', ['uses' => 'PracticesController@getApproveLogsViewRefresh'])->name('practices.getApproveLogsViewRefresh');
	Route::post('submitLogForMultipleDates', ['uses' => 'PracticesController@submitLogForMultipleDates']);
	Route::post('submitLogForOnCall', ['uses' => 'PhysiciansController@submitLogForOnCall']);
	Route::post('checkDuretion', ['uses' => 'PhysiciansController@checkDuretion']);
	Route::post('postSaveLog', ['uses' => 'PhysicianLogManagerController@postSaveLog']); // This is the generalised save log method for web and mobile application.
	Route::get('deleteLog/{log_id}', ['uses' => 'PracticesController@deleteOnCallEntry']);
	Route::post('practices/{id}/contract/{contract_id}/logEntry', ['uses' => 'PracticesController@approveOnCallLogs']);
	Route::post('reSubmitLog', ['uses' => 'PhysiciansController@reSubmitLog']);
	Route::post('reSubmitEditLog', ['uses' => 'PhysiciansController@reSubmitEditLog']);
	
	Route::post('actions', ['uses' => 'PracticesController@getActions']);
	Route::get('practices/deleteLog/{log_id}', ['uses' => 'PracticesController@deleteLog'])->name('log.delete');
	Route::get('practices/{id}/contract/{contract_id}/rejectedLogs/{physician_id}', ['uses' => 'PracticesController@getShowRejectedLogs'])->name('practices.show_rejected_logs');

// ---------------------------------------------------------------------------
// Physicians Routes
// ---------------------------------------------------------------------------
	Route::get('physicians', ['uses' => 'PhysiciansController@getIndex'])->name('physicians.index');
	Route::get('physiciansShowAll', ['uses' => 'PhysiciansController@getIndexShowAll'])->name('physicians.index_show_all');
	Route::get('physicians/create', ['uses' => 'PhysiciansController@getCreate'])->name('physicians.create');
	Route::post('physicians/create', ['uses' => 'PhysiciansController@postCreate']);
//physcian to multiple by 1254 : added pid
	Route::get('physicians/{id}/{pid}', ['uses' => 'PhysiciansController@getShow'])->name('physicians.show');
	Route::get('physicians/{id}/edit/{pid}', ['uses' => 'PhysiciansController@getEdit'])->name('physicians.edit');
	Route::post('physicians/{id}/edit/{pid}', ['uses' => 'PhysiciansController@postEdit']);
	Route::get('physicians/{id}/delete/{pid}', ['uses' => 'PhysiciansController@getDelete'])->name('physicians.delete');
	Route::get('physicians/{id}/reset-password/{pid}', ['uses' => 'PhysiciansController@getResetPassword'])->name('physicians.reset_password')->middleware(['throttle:api']);
	Route::get('physicians/{id}/welcome/{pid}', ['uses' => 'PhysiciansController@getWelcome'])->name('physicians.welcome');
	
	Route::group([
		"middleware" => ["auth", "bindings", "accessCheckFilter:$superuser|$superhospitaluser|$hospitaluser|$physicians|$healthsystemuser|$healthsystemregionuser"]
	], function () {
		Route::get('contracts/{contract}/paymentmanagement/{practice}/physician/{physician}', ['uses' => 'ContractsController@paymentManagement'])->name('contracts.paymentmanagement')->can('view', ['contract']);
		Route::get('contracts/{contract}/unapprovelogs/{practice}/physician/{physician}', ['uses' => 'ContractsController@getUnapproveLogs'])->name('contracts.unapprovelogs')->can('view', ['contract']);
		Route::get('contracts/{contract}/copycontract/{practice}/physician/{physician}', ['uses' => 'ContractsController@getCopyContract'])->name('contracts.copycontract')->can('view', ['contract']);
		Route::get("contracts/{contract}/edit/{practice}/physician/{physician}", ["uses" => "ContractsController@getEdit"])->name('contracts.edit')->can('view', ['contract']);
		Route::get('contracts/{contract}/interfacedetails/{practice}', ['uses' => 'ContractsController@interfaceDetails'])
			->name('contracts.interfacedetails')->can('viewContract', ['contract', 'practice']);
		Route::get("contracts/{contract}", ["uses" => "ContractsController@show"])->name('contracts.show')->can('viewAuditHistory', 'contract');
		Route::get('physicians/{physician}/contracts/{practice}', ['uses' => 'PhysiciansController@getContracts'])->name('physicians.contracts')->can('viewContractByPhysician', ['physician', 'practice']);
		Route::get('physicians/{physician}/contracts/create/{practice}', ['uses' => 'PhysiciansController@getCreateContract'])->name('physicians.create_contract')->can('createContract', ['physician', 'practice']);
		Route::get("contracts/{contract}/delete/{practice}", ["uses" => "ContractsController@getDelete"])->name('contracts.delete')->can('delete', ['contract', 'practice']);
		Route::get("contracts/{contract}/archive", ['uses' => 'ContractsController@getArchive'])->name('contracts.archive')->can('viewArchive', ['contract']);
		Route::get('contracts/{contract}/unarchive', ['uses' => 'ContractsController@getUnarchive'])->name('contracts.unarchive')->can('viewArchive', ['contract']);
		Route::get("contracts/{contract}/{physician}/displayContractApprovers", ["uses" => "ContractsController@getDisplayContractApprovers"])->name('contracts.displayContractApprovers')->can('view', ['contract']);
		Route::get("/getPhysicianLogsInApprovalQueue/{agreement}/{contract}", ["uses" => "ContractsController@getPhysicianLogsInApprovalQueue"])->can('viewAgreementsContract', ['agreement', 'contract']);
		Route::get("contracts/{contract}/edit/psa", ["uses" => "ContractsController@getPsaEdit"])->name('contracts_psa.edit')->can('update', 'contract');
	});
	
	Route::group([
		"middleware" => ["auth", "bindings", "accessCheckFilter:$superuser"]
	], function () {
		Route::get("/updateSplitPayment", ["uses" => "ContractsController@updateSplitPayment"]);
		Route::get("/updateSortingContractNames", ["uses" => "ContractsController@updateSortingContractNames"]);
		Route::get("/updateSortingContractActivities", ["uses" => "ContractsController@updateSortingContractActivities"]);
		Route::get('contractRateUpdate', ['uses' => 'ContractsController@contract_rate_update']);
		Route::get('oneToManyUpdate', ['uses' => 'ContractsController@onetomany_update']);
		Route::get('customeInvoiceUpdate', ['uses' => 'ContractsController@custome_invoice_update']);
// updating the life points health system hospitals to new invoice_type flag to 1.
		Route::get('hospitalUpdateToInvoiceNotesTable', ['uses' => 'ContractsController@hospital_update_to_invoice_notes_table']); // updating the hospital_id in invoice _notes table
		Route::get('updateLogHours', ['uses' => 'ContractsController@update_log_hours']);
		Route::get('updatePartialHoursCalculation', ['uses' => 'ContractsController@update_partial_hours_calculation']); //update partial hours calculation to
		Route::get('/contractdocument/{contract_document}', ['uses' => 'ContractsController@getContractDocument'])->name('contract.document');
		Route::get("/updateSortingContractNamesByHospital/{id}", ["uses" => "ContractsController@updateSortingContractNamesByHospital"]);
	});
	
	Route::post('physicians/{id}/contracts/create/{pid}', ['uses' => 'PhysiciansController@postCreateContract']);
	Route::get('physiciansDeleted', ['uses' => 'PhysiciansController@getDeleted'])->name('physicians.deleted');
	Route::get('physiciansRestore/{id}/{pid}', ['uses' => 'PhysiciansController@getRestore'])->name('physicians.restore');
	
	Route::get('/getsortingcontractnames/{pr_id}/{ph_id}', ['uses' => 'PhysiciansController@getsortingcontractnames']);
	Route::post('/savesortingcontractnames', ['uses' => 'PhysiciansController@postSortingContractNames']);
	
	Route::post('physicians/{id}/contracts/{cid}/edit', ['uses' => 'PhysiciansController@postEditContract']);
	Route::get('physicians/{id}/logs/{pid}', ['uses' => 'PhysiciansController@getLogs'])->name('physicians.logs');
	Route::get('physicians/{id}/logs/{lid}/delete', ['uses' => 'PhysiciansController@getDeleteLog'])->name('physicians.delete_log');
	Route::get('physicians/{id}/{pid}/reports', ['uses' => 'PhysiciansController@getReports'])->name('physicians.reports');
	Route::post('physicians/{id}/{pid}/reports', ['uses' => 'PhysiciansController@postReports']);
	Route::get('physicians/{id}/reports/{rid}', ['uses' => 'PhysiciansController@getReport'])->name('physicians.report');
	Route::get('physicians/{id}/reports/{rid}/delete', ['uses' => 'PhysiciansController@getDeleteReport'])->name('physicians.delete_report');
	Route::get("physicians/{id}/payments", ["uses" => "PhysiciansController@getPayments"])->name('physicians.payments');
	Route::post("physicians/{id}/payments", ["uses" => "PhysiciansController@postPayments"]);
	Route::get("physicians/{id}/payments/{pid}/delete", ["uses" => "PhysiciansController@getDeletePayment"])->name('physicians.delete_payment');
	Route::get('physicians/{id}/editPractice/{pid}/{hid}', ['uses' => 'PhysiciansController@editPractice'])->name('physicians.editPractice');
	Route::post('physicians/{id}/editPractice/{pid}/{hid}', ['uses' => 'PhysiciansController@postEditPractice']);
	Route::get('physician/{id}', ['uses' => 'PhysiciansController@physicianDashboard'])->name('physician.dashboard');
	
	Route::get('physician/hospitalcontracts/{hospital_id}/{physician_id}', 'PhysiciansController@getContractsForHospitals');
	Route::get('physicians/{id}/{pid}/signature', ['uses' => 'PhysiciansController@getSignature'])->name('physicians.signature');
	Route::get('physicians/{id}/{pid}/signature_edit', ['uses' => 'PhysiciansController@getSignature_edit'])->name('physicians.signature_edit');
	Route::get('physicians/{id}/signatureApprove/{c_id}/{date_selector}', ['uses' => 'PhysiciansController@getApproveSignature'])->name('physicians.signatureApprove');
	Route::get('physicians/{id}/signatureApprove_edit/{c_id}/{date_selector}', ['uses' => 'PhysiciansController@getApproveSignature_edit'])->name('physicians.signatureApprove_edit');
	Route::post('physiciansSignature', ['uses' => 'PhysiciansController@postSignature']);
	Route::post('physiciansApproveSignature', ['uses' => 'PhysiciansController@postApproveSignature']);
	Route::get('physicians/{id}/signatureApprove/{c_id}/signature/{s_id}', ['uses' => 'PhysiciansController@approveLogs'])->name('physicians.approveLogs');
	Route::post('signatureApprove', ['uses' => 'PhysiciansController@approveAllLogs']);
	Route::get('physician/{id}/changePassword', ['uses' => 'PhysiciansController@getChangePassword'])->name('physician.changePassword');
	Route::post('physician/{id}/changePassword', ['uses' => 'ApiController@postProfile']);
	Route::get('physician/{id}/getRejected/{c_id}/{hid}', ['uses' => 'PhysiciansController@getRejected'])->name('physician.rejected');
	Route::get('physicians/{id}/contracts/create/{pid}/checkApproval/{agreement_id}', ['uses' => 'PhysiciansController@checkAgreementApproval'])->name('physicians.check_approval');
	Route::get('contracts/{c_id}/edit/{pid}/checkApproval/{agreement_id}', ['uses' => 'PhysiciansController@checkAgreementApproval'])->name('contracts.check_approval');
	Route::get('agreements/{id}/createContract/checkApproval', ['uses' => 'AgreementsController@checkAgreementApproval'])->name('agreement.check_approval');
	Route::get('physician/{id}/expired', ['uses' => 'PhysiciansController@getExpired'])->name('physician.expired');
	Route::post('physician/{id}/expired', ['uses' => 'ApiController@postProfile']);
	Route::get('physicians/{id}/interfacedetails/{pid}', ['uses' => 'PhysiciansController@interfaceDetails'])->name('physicians.interfacedetails');
	Route::post('physicians/{id}/interfacedetails/{pid}', ['uses' => 'PhysiciansController@postInterfaceDetails']);
	Route::get('physician_report/{id}/report', ['uses' => 'PhysiciansController@getPhysicianHospitalReports'])->name('physician.reports');
	Route::post('physician_report/{id}/report', ['uses' => 'PhysiciansController@postPhysicianHospitalReports']);
	Route::get('physician_report/{id}/reports/{rid}', ['uses' => 'PhysiciansController@getPhysicianHospitalReport'])->name('physician.report');
	Route::get('physician_report/{id}/paymentStatusReports', ['uses' => 'PhysiciansController@getpaymentStatusReports'])->name('physician.paymentStatusReport');
	Route::post('physician_report/{id}/paymentStatusReports', ['uses' => 'PhysiciansController@postpaymentStatusReports']);
	Route::get('physician_report/{id}/reports/{report}/delete', ['uses' => 'PhysiciansController@getphysicianHospitalDeleteReport'])->name('physician.delete_report');
	Route::get("/getContractTypesByPhysician/{p_id}/{h_id}/{f_id}", ["uses" => "PhysiciansController@getContractTypesByPhysician"])->name('physician.getContractTypesByPhysician');

// ---------------------------------------------------------------------------
// Practice Types Routes
// ---------------------------------------------------------------------------
	Route::group(['middleware' => 'auth'], function () {
		Route::get('practice-types', ['uses' => 'PracticeTypesController@getIndex'])->name('practice_types.index');
		Route::get('practice-types/create', ['uses' => 'PracticeTypesController@getCreate'])->name('practice_types.create');
		Route::post('practice-types/create', ['uses' => 'PracticeTypesController@postCreate']);
		Route::get('practice-types/{id}/edit', ['uses' => 'PracticeTypesController@getEdit'])->name('practice_types.edit');
		Route::post('practice-types/{id}/edit', ['uses' => 'PracticeTypesController@postEdit']);
		Route::get('practice-types/{id}/delete', ['uses' => 'PracticeTypesController@getDelete'])->name('practice_types.delete');
	});

// ---------------------------------------------------------------------------
// Users Routes
// ---------------------------------------------------------------------------
	Route::group(['middleware' => 'auth'], function () {
		Route::get('users', ['uses' => 'UsersController@getIndex'])->name('users.index');
		Route::get('usersShowAll', ['uses' => 'UsersController@getIndexShowAll'])->name('users.index_show_all');
		Route::get('users/create', ['uses' => 'UsersController@getCreate'])->name('users.create');
		Route::post('users/create', ['uses' => 'UsersController@postCreate']);
		Route::get('users/{id}', ['uses' => 'UsersController@getShow'])->name('users.show');
		Route::get('users/{id}/edit', ['uses' => 'UsersController@getEdit'])->name('users.edit');
		Route::post('users/{id}/edit', ['uses' => 'UsersController@postEdit']);
		Route::get('users/{id}/add_proxy', ['uses' => 'UsersController@getProxy'])->name('users.add_proxy');
		Route::post('users/{id}/add_proxy', ['uses' => 'UsersController@postProxyUser']);
		Route::get('users/{id}/delete_proxy', ['uses' => 'UsersController@getDeleteProxy'])->name('proxy_user.delete');
		Route::get('users/{id}/delete', ['uses' => 'UsersController@getDelete'])->name('users.delete');
		Route::get('users/{id}/reset-password', ['uses' => 'UsersController@getResetPassword'])->name('users.reset_password')->middleware(['throttle:api']);
		Route::get('users/{id}/welcome', ['uses' => 'UsersController@getWelcome'])->name('users.welcome');
		Route::get('usersDeleted', ['uses' => 'UsersController@getDeleted'])->name('users.deleted');
		Route::get('usersRestore/{id}', ['uses' => 'UsersController@getRestore'])->name('users.restore');
		Route::get('users/{id}/expired', ['uses' => 'UsersController@getExpired'])->name('users.expired');
		Route::post('users/{id}/expired', ['uses' => 'UsersController@postExpired']);
		Route::get('users/{id}/{hospital_id}', ['uses' => 'UsersController@getShow'])->name('users.adminshow');
		Route::get('users/{id}/edit/{hospital_id}', ['uses' => 'UsersController@getEdit'])->name('users.adminedit');
		Route::post('users/{id}/edit/{hospital_id}', ['uses' => 'UsersController@postEdit']);
		Route::get('users/{id}/delete/{hospital_id}', ['uses' => 'UsersController@getDelete'])->name('users.admindelete');
		Route::get('users/{id}/reset-password/{hospital_id}', ['uses' => 'UsersController@getResetPassword'])->name('users.admin_reset_password')->middleware(['throttle:api']);
		Route::get('users/{id}/welcome/{hospital_id}', ['uses' => 'UsersController@getWelcome'])->name('users.admin_welcome');
		Route::get("/show_user_password/{id}", ["uses" => "UsersController@getDecryptedPassword"])->name('users.showpassword');
		Route::get("/show_physician_password/{id}", ["uses" => "PhysiciansController@getDecryptedPassword"])->name('physicians.showpassword');
		Route::get("/user/password_update", ["uses" => "UsersController@updateUsersPassword"]);
	});
	
	Route::post("/api/v2/authenticate", ["uses" => "ApiController@postAuthenticate"]);
	Route::get("/api/v2/profile", ["uses" => "ApiController@getProfile"]);
	Route::post("/api/v2/profile", ["uses" => "ApiController@postProfile"]);
	Route::get("/api/v2/contracts", ["uses" => "ApiController@getContracts"]);
	Route::get("/api/v3/contracts", ["uses" => "ApiController@getContractsRecent"]);
	Route::get("/api/v2/hospitals", ["uses" => "ApiController@getHospitals"]);
	Route::post("/api/v2/logs/save", ["uses" => "ApiController@postSaveLog"]);
	Route::post("/api/v3/logs/save", ["uses" => "ApiController@postSaveLogRange"]);
	Route::post("/api/v2/logs/saveLogForOnCallContract", ["uses" => "ApiController@postSaveLogForMultipleDates"]);
	Route::post("/api/v3/logs/saveLogForOnCallContract", ["uses" => "ApiController@postSaveLogForMultipleDatesRange"]);
	Route::post("/api/v2/logs/delete", ["uses" => "ApiController@postDeleteLog"]);
	Route::get("/api/v2/createTable", ["uses" => "ApiController@createTable"]);
	Route::post("/api/v2/isSignatureSubmitted", ["uses" => "ApiController@isSignatureSubmitted"]);
	Route::get("/api/v2/signature", ["uses" => "ApiController@getSignature"]);
	Route::post("/api/v2/submitSignature", ["uses" => "ApiController@submitSignature"]);
	Route::get("/api/v2/DeleteAllSignature", ["uses" => "ApiController@deleteAllSignature"]);
	Route::post("/api/v2/logs/saveTimeStudyLog", ["uses" => "ApiController@postSaveTimeStudyLog"]);
	Route::get("/api/v2/priorMonthLogs", ["uses" => "ApiController@getPriorMonthLogs"]);
	Route::get("/api/v3/priorMonthLogs", ["uses" => "ApiController@getPriorMonthLogsRecent"]);
	Route::post("/api/v2/submitSignatureForLog", ["uses" => "ApiController@submitSignatureForLog"]);
	Route::post("/api/v2/checkPriorMonthLogsApproval", ["uses" => "ApiController@checkPriorMonthLogsApproval"]);
	Route::post("/api/v2/reSubmitLog", ["uses" => "ApiController@reSubmitLog"]);
	Route::post("/api/v2/reSubmitUnapprovedLog", ["uses" => "ApiController@reSubmitUnapprovedLog"]);
	Route::post("/api/v3/reSubmitUnapprovedLog", ["uses" => "ApiController@reSubmitUnapprovedLogRecent"]);
	Route::get("/api/v2/getUnapproveLogs", ["uses" => "ApiController@getUnapproveLogs"]);
	Route::get("/api/v2/getLogOut", ["uses" => "ApiController@logOut"]);
	Route::get("/api/v2/lockedCheck", ["uses" => "ApiController@lockedCheck"]);
	Route::get("/api/v2/expiredCheck", ["uses" => "ApiController@expiredCheck"]);
	Route::post("/api/v2/sendOTP", ["uses" => "ApiController@sendOTP"]);
	Route::post("/api/v2/verifyOTP", ["uses" => "ApiController@verifyOTP"]);
	Route::post("/api/v2/resetPassword", ["uses" => "ApiController@resetPassword"])->middleware(['throttle:api']);
	Route::get("/api/v2/versionCheck", ["uses" => "ApiController@versionCheck"]);
	Route::get("/api/v2/checkAttestationsExist", ["uses" => "ApiController@checkAttestationsExist"]);
	Route::post("/api/v2/saveAttestations", ["uses" => "ApiController@saveAttestations"]);

// ---------------------------------------------------------------------------
// Log Reminder Mail Route
// ---------------------------------------------------------------------------
	Route::get('getLogReminderForPhysicians', ['uses' => 'LogReminderController@getLogReminderForPhysicians']);
	Route::get('getLogReminderForPhysiciansDirectorship/{typetype}', ['uses' => 'LogReminderController@getLogReminderForPhysiciansDirectership']);

// ---------------------------------------------------------------------------
// Renew Contract Mail Route
// ---------------------------------------------------------------------------
	Route::get('renewalReminderMail', ['uses' => 'AgreementExpiryReminderController@renewalReminderMail']);
	Route::get('reportReminderMail', ['uses' => 'AgreementExpiryReminderController@reportReminderMail']);

//----------------------------------------------------------------------------
// Log Approval
// ---------------------------------------------------------------------------
	
	Route::group(['middleware' => 'auth'], function () {
		Route::get('showPerformanceDashboard', ['uses' => 'PerformanceController@showPerformanceDashboard'])->name('performance_dashboard.display'); //Chaitraly::Added new route for performance dashboard
		Route::get('getLogsForApproval', ['uses' => 'ApprovalController@index'])->name('approval.index');
		Route::post('getLogsForApproval', ['uses' => 'ApprovalController@save']);
		Route::get('getSummationDataLevelOne', ['uses' => 'ApprovalController@getSummationDataLevelOne'])->name('approval.getSummationDataLevelOne');
		Route::get('getSummationDataLevelTwo', ['uses' => 'ApprovalController@getSummationDataLevelTwo'])->name('approval.getSummationDataLevelTwo');
		Route::get('getSummationDataLevelThree', ['uses' => 'ApprovalController@getSummationDataLevelThree'])->name('approval.getSummationDataLevelThree');
		Route::post('userSignature', ['uses' => 'ApprovalController@approveLog']);
		Route::get('editUserSignature/{log_ids}/{rejected}/{rejected_with_reasons}/{manager_types}', ['uses' => 'ApprovalController@editSignature'])->name('approval.edit');
		Route::get('veiwUserSignature', ['uses' => 'ApprovalController@getSignature'])->name('approval.signature');
		Route::get('editUserSignature', ['uses' => 'ApprovalController@changeSignature'])->name('approval.editSignature');
		Route::post('approvalStatusReport', ['uses' => 'ApprovalController@approvalStatusReport']);
		Route::post('paymentStatusReport', ['uses' => 'ApprovalController@paymentStatusReport']);
		Route::get('paymentStatusOld', ['uses' => 'ApprovalController@paymentStatus'])->name('approval.paymentStatusOld');
		Route::get('paymentStatus', ['uses' => 'ApprovalController@paymentStatusLevelOne'])->name('approval.paymentStatus');
		Route::get('paymentStatusLevelTwo', ['uses' => 'ApprovalController@paymentStatusLevelTwo'])->name('payment_status.paymentStatusLevelTwo');
		Route::get('paymentStatusLevelThree', ['uses' => 'ApprovalController@paymentStatusLevelThree'])->name('payment_status.paymentStatusLevelThree');
		Route::get('approvalStatusReport/{report_id}', ['uses' => 'ApprovalController@getStatusReport'])->name('approval.report');
		Route::get('getAllLogIdsForApproval', ['uses' => 'ApprovalController@getAllLogIdsForApproval'])->name('approval.getAllLogIdsForApproval');
		
		Route::get('columnPreferencesPaymentStatus', ['uses' => 'ApprovalController@columnPreferencesPaymentStatus'])->name('approval.columnPreferencesPaymentStatus');
		Route::post('columnPreferencesPaymentStatus', ['uses' => 'ApprovalController@postColumnPreferencesPaymentStatus']);
		Route::get('columnPreferencesApprovalDashboard', ['uses' => 'ApprovalController@columnPreferencesApprovalDashboard'])->name('approval.columnPreferencesApprovalDashboard');
		Route::post('columnPreferencesApprovalDashboard', ['uses' => 'ApprovalController@postColumnPreferencesApprovalDashboard']);
		Route::post("/getTotalHoursForPhysicanLog", ["uses" => "PerformanceController@getTotalHoursForPhysicanLog"])->name('approval.getTotalHoursForPhysicanLog');
		Route::get('getPerformance/PhysicianReports', ['uses' => 'PerformanceController@getPhysicianPerformanceReport'])->name('performance.report');
		Route::post('getPerformance/PhysicianReports', ['uses' => 'PerformanceController@postPhysicianPerformanceReport']);
		Route::get('getPerformance/ApproverReports', ['uses' => 'PerformanceController@getApproverPerformanceReport'])->name('performance.approverReport');
		Route::post('getPerformance/ApproverReports', ['uses' => 'PerformanceController@postApproverPerformanceReport']);
		Route::get('performance/reports/{report}/delete', ['uses' => 'PerformanceController@getDeleteReport'])->name('performance.delete_report');
		Route::get("/getPerformanceAgreementsByHospital/{h_id}", ["uses" => "PerformanceController@getPerformanceAgreementsByHospital"]);
		Route::get("/getPhysiciansByHospital/{h_id}/{c_id}", ["uses" => "PerformanceController@getPhysiciansByHospital"]);
		Route::get("/getApproversByHospital/{h_id}/{c_id}", ["uses" => "PerformanceController@getApproversByHospital"]);
		Route::get("/getTimePeriodByAgreements/{h_id}/{a_id}", ["uses" => "PerformanceController@getTimePeriodByAgreements"]);
		Route::get("/getContractTypesForPerformanceReport/{h_id}", ["uses" => "PerformanceController@getContractTypesForPerformanceReport"]);
		
		Route::post("/getPhysicianLogsList", ["uses" => "PerformanceController@getPhysicianLogsList"])->name('performance.getPhysicianLogsList');
		Route::get("/getManagementContractTypeChart/{r_id}/{h_id}/{p_id}/{c_id}/{group_id}", ["uses" => "PerformanceController@getManagementContractTypeChart"])->name('performance.getManagementContractTypeChart');
		Route::get("/getManagementSpecialtyChart/{r_id}/{h_id}/{p_id}/{s_id}/{group_id}/{c_id}", ["uses" => "PerformanceController@getManagementSpecialtyChart"])->name('performance.getManagementSpecialtyChart');
		Route::get("/getManagementProviderChart/{r_id}/{h_id}/{p_id}/{pr_id}/{group_id}/{c_id}/{s_id}", ["uses" => "PerformanceController@getManagementProviderChart"])->name('performance.getManagementProviderChart');
		Route::get("/getActualToExpectedTimeContractTypeChart/{r_id}/{h_id}/{p_id}/{c_id}/{group_id}", ["uses" => "PerformanceController@getActualToExpectedTimeContractTypeChart"])->name('performance.getActualToExpectedTimeContractTypeChart');
		Route::get("/getActualToExpectedTimeSpecialtyChart/{r_id}/{h_id}/{p_id}/{s_id}/{group_id}/{c_id}", ["uses" => "PerformanceController@getActualToExpectedTimeSpecialtyChart"])->name('performance.getActualToExpectedTimeSpecialtyChart');
		Route::get("/getActualToExpectedTimeProviderChart/{r_id}/{h_id}/{p_id}/{pr_id}/{group_id}/{c_id}/{s_id}", ["uses" => "PerformanceController@getActualToExpectedTimeProviderChart"])->name('performance.getActualToExpectedTimeProviderChart');
		Route::get("/getContractTypesForPerformansDashboard/{r_id}/{h_id}/{p_id}/{group_id}", ["uses" => "PerformanceController@getContractTypesForPerformansDashboard"])->name('performance.getContractTypesForPerformansDashboard');
		Route::get("/getSpecialtiesForPerformansDashboard/{r_id}/{h_id}/{p_id}/{group_id}/{c_id}", ["uses" => "PerformanceController@getSpecialtiesForPerformansDashboard"])->name('performance.getSpecialtiesForPerformansDashboard');
		Route::get("/getProvidersForPerformansDashboard/{r_id}/{h_id}/{p_id}/{group_id}/{c_id}/{s_id}", ["uses" => "PerformanceController@getProvidersForPerformansDashboard"])->name('performance.getProvidersForPerformansDashboard');
		Route::get("/getManagementDutyPopUp/{r_id}/{h_id}/{p_id}/{ct_id}/{sp_id}/{ph_id}/{group_id}/{category_id}", ["uses" => "PerformanceController@getManagementDutyPopUp"])->name('performance.getManagementDutyPopUp');
		Route::get("/getActualToExpectedPopUp/{r_id}/{h_id}/{p_id}/{ct_id}/{sp_id}/{ph_id}/{group_id}", ["uses" => "PerformanceController@getActualToExpectedPopUp"])->name('performance.getActualToExpectedPopUp');
		Route::get('performance/reports/{report}', ['uses' => 'PerformanceController@downloadReport'])->name('performance.getReport');
	});

// ---------------------------------------------------------------------------
// Physician Reminder Mail Route
// ---------------------------------------------------------------------------
	Route::get('enterLog', ['uses' => 'PhysicianReminderController@enterLog']);
	Route::get('approveLog', ['uses' => 'PhysicianReminderController@approveLog']);

// ---------------------------------------------------------------------------
// Approval Reminder Mail for Managers Route
// ---------------------------------------------------------------------------
	Route::get('approveLogCM', ['uses' => 'ApprovalReminderController@approveLogCM']);
	Route::get('approveLogFM', ['uses' => 'ApprovalReminderController@approveLogFM']);
	Route::get('approveLogEM', ['uses' => 'ApprovalReminderController@approveLogEM']);
	Route::get('invoiceLogReport', ['uses' => 'ApprovalReminderController@invoiceWithLogReports']);
	
	/*--------------------------------------------------------------------------------*/
	/* Payment type
	/*----------------------------------*/
	Route::get("/payment_types", ["uses" => "PaymentTypesController@getIndex"])->name('payment_types.index');
	Route::get("/payment_types/create", ["uses" => "PaymentTypesController@getCreate"])->name('payment_types.create');
	Route::post("/payment_types/create", ["uses" => "PaymentTypesController@postCreate"]);
	Route::get("/payment_types/{id}/edit", ["uses" => "PaymentTypesController@getEdit"])->name('payment_types.edit');
	Route::post("/payment_types/{id}/edit", ["uses" => "PaymentTypesController@postEdit"]);
	Route::get("/payment_types/{id}/delete", ["uses" => "PaymentTypesController@getDelete"])->name('payment_types.delete');
	
	/*--------------------------------------------------------------------------------*/
	/*Health System and Region*/
	/*--------------------------------------------------------------------------------*/
	Route::group(['middleware' => 'auth'], function () {
		Route::get('healthSystem', ['uses' => 'HealthSystemController@getIndex'])->name('healthSystem.index');
		Route::get('healthSystem/create', ['uses' => 'HealthSystemController@getCreate'])->name('healthSystem.create');
		Route::post('healthSystem/create', ['uses' => 'HealthSystemController@postCreate']);
		Route::get('healthSystem/{id}', ['uses' => 'HealthSystemController@getShow'])->name('healthSystem.show');
		Route::get('healthSystem/{id}/edit', ['uses' => 'HealthSystemController@getEdit'])->name('healthSystem.edit');
		Route::post('healthSystem/{id}/edit', ['uses' => 'HealthSystemController@postEdit']);
		Route::get('healthSystem/{id}/users', ['uses' => 'HealthSystemController@getUsers'])->name('healthSystem.users');
		Route::get('healthSystem/{id}/user/create', ['uses' => 'HealthSystemController@getCreateUser'])->name('healthSystem.create_user');
		Route::post('healthSystem/{id}/user/create', ['uses' => 'HealthSystemController@postCreateUser']);
		
		Route::get('healthSystem/{id}/users/add', ['uses' => 'HealthSystemController@getAddUser'])->name('healthSystem.add_user');
		Route::post('healthSystem/{id}/users/add', ['uses' => 'HealthSystemController@postAddUser']);
		Route::get("getAgreementDataByAjaxForHealthSystem/{group_id}", ["uses" => "HealthSystemController@getAgreementDataByAjaxForHealthSystem"])->name('healthSystem.getAgreementDataByAjaxForHealthSystem');
		
		Route::get('healthSystem/{id}/delete', ['uses' => 'HealthSystemController@getDelete'])->name('healthSystem.delete');
		Route::get('healthSystem/{id}/regions', ['uses' => 'HealthSystemController@getRegions'])->name('healthSystem.regions');
		Route::get('healthSystem/{id}/region/create', ['uses' => 'HealthSystemController@getCreateRegion'])->name('healthSystem.create_region');
		Route::post('healthSystem/{id}/region/create', ['uses' => 'HealthSystemController@postCreateRegion']);
		Route::get('healthSystem/{id}/region/{rid}', ['uses' => 'HealthSystemRegionController@getShow'])->name('healthSystemRegion.show');
		Route::get('healthSystem/{id}/region/{rid}/edit', ['uses' => 'HealthSystemRegionController@getEdit'])->name('healthSystemRegion.edit');
		Route::post('healthSystem/{id}/region/{rid}/edit', ['uses' => 'HealthSystemRegionController@postEdit']);
		Route::get('healthSystem/{id}/region/{rid}/users', ['uses' => 'HealthSystemRegionController@getUsers'])->name('healthSystemRegion.users');
		Route::get('healthSystem/{id}/region/{rid}/user/create', ['uses' => 'HealthSystemRegionController@getCreateUser'])->name('healthSystemRegion.create_user');
		Route::post('healthSystem/{id}/region/{rid}/user/create', ['uses' => 'HealthSystemRegionController@postCreateUser']);
		
		Route::get('healthSystem/{id}/region/{rid}/user/add', ['uses' => 'HealthSystemRegionController@getAddUser'])->name('healthSystemRegion.add_user');
		Route::post('healthSystem/{id}/region/{rid}/user/add', ['uses' => 'HealthSystemRegionController@postAddUser']);
		
		Route::get('healthSystem/{id}/region/{rid}/delete', ['uses' => 'HealthSystemRegionController@getDeleteRegion'])->name('healthSystemRegion.delete');
		Route::get('healthSystem/{id}/region/{rid}/hospitals', ['uses' => 'HealthSystemRegionController@getRegionHospitals'])->name('healthSystemRegion.hospitals');
		Route::get('healthSystem/{id}/region/{rid}/hospital/associate', ['uses' => 'HealthSystemRegionController@getAddRegionHospital'])->name('healthSystemRegion.add_hospital');
		Route::post('healthSystem/{id}/region/{rid}/hospital/associate', ['uses' => 'HealthSystemRegionController@postAddRegionHospital']);
		Route::get('healthSystem/{id}/region/{rid}/hospital/disassociate/{h_id}', ['uses' => 'HealthSystemRegionController@getDisassociateRegionHospital'])->name('healthSystemRegion.disassociate_hospital');
		Route::get('healthSystem/reports/activeContractsReport/{group_id}', ['uses' => 'HealthSystemController@getReports'])->name('healthSystem.activeContractsReport');
		Route::post('healthSystem/reports/activeContractsReport/{group_id}', ['uses' => 'HealthSystemController@postActiveContractsReports']);
		Route::get('healthSystem/reports/contractsExpiringReport/{group_id}', ['uses' => 'HealthSystemController@getContractExpiringReports'])->name('healthSystem.contractsExpiringReport');
		Route::post('healthSystem/reports/contractsExpiringReport/{group_id}', ['uses' => 'HealthSystemController@postContractsExpiringReport']);
		Route::get('healthSystem/reports/providerProfileReport/{group_id}', ['uses' => 'HealthSystemController@getProviderProfileReports'])->name('healthSystem.providerProfileReport');
		Route::post('healthSystem/reports/providerProfileReport/{group_id}', ['uses' => 'HealthSystemController@postProviderProfileReports']);
		Route::get('healthSystem/reports/spendYTDEffectivenessReport/{group_id}', ['uses' => 'HealthSystemController@getspendYTDEffectivenessReports'])->name('healthSystem.spendYTDEffectivenessReport');
		Route::post('healthSystem/reports/spendYTDEffectivenessReport/{group_id}', ['uses' => 'HealthSystemController@postspendYTDEffectivenessReports']);
		Route::get('healthSystem/reports/{report}/{group_id}', ['uses' => 'HealthSystemController@getReport'])->name('healthSystem.report');
		Route::get('hospitals/reports/{report}/delete/{group_id}', ['uses' => 'HealthSystemController@getDeleteReport'])->name('healthSystem.delete_report');
		Route::get('getRegionFacilitiesContractTypes/{r_id}', ["uses" => "HealthSystemController@getRegionFacilitiesCTypePTypeData"])->name('healthSystem.getRegionFacilitiesContractTypes');
		Route::get('getFacilitiesContractTypes/region/{r_id}/hospital/{h_id}', ["uses" => "HealthSystemController@getFacilitiesCTypePTypeData"])->name('healthSystem.getFacilitiesContractTypes');
		Route::get('getPaymentTypeContractTypes/region/{r_id}/hospital/{h_id}/payment_type/{p_id}', ["uses" => "HealthSystemController@getPTypeCTypeData"])->name('healthSystem.getPaymentTypeContractTypes');
		Route::get("showHealthSystemDashboard", ["uses" => "DashboardController@showHealthSystemDashboard"])->name('healthsystem_dashboard.display');
		Route::get("showHealthSystemRegionDashboard", ["uses" => "DashboardController@showHealthSystemRegionDashboard"])->name('healthsystemregion_dashboard.display');
		Route::get("getHospitalAgreementStartEndDate/{region}/{h_id}", ["uses" => "DashboardController@getHospitalAgreementStartEndDate"]);
		
		Route::post('hospitals/{id}/admins/updateInvoiceDisplayStatus', ['uses' => 'HospitalsController@updateInvoiceDisplayStatus']);
	});
	
	Route::group(['middleware' => 'auth'], function () {
		Route::get("showComplianceDashboard", ["uses" => "DashboardController@showComplianceDashboard"])->name('compliance_dashboard.display');
		Route::get("/getRejectionRateChart/{h_id}", ["uses" => "DashboardController@getRejectionRateChart"])->name('dashboard.getRejectionRateChart');
		Route::get("/getRejectionRateOverallcomparedChart/{h_id}", ["uses" => "DashboardController@getRejectionRateOverallcomparedChart"])->name('dashboard.getRejectionRateOverallcomparedChart');
		Route::get("/getRejectionByphysicianChart/{h_id}", ["uses" => "DashboardController@getRejectionByphysicianChart"])->name('dashboard.getRejectionByphysicianChart');
		Route::get("/getRejectionByContractTypeChart/{h_id}", ["uses" => "DashboardController@getRejectionByContractTypeChart"])->name('dashboard.getRejectionByContractTypeChart');
		Route::get("/getRejectionByPracticeChart/{h_id}", ["uses" => "DashboardController@getRejectionByPracticeChart"])->name('dashboard.getRejectionByPracticeChart');
		Route::get("/getRejectionByReasonChart/{h_id}", ["uses" => "DashboardController@getRejectionByReasonChart"])->name('dashboard.getRejectionByReasonChart');
		Route::get("/getRejectionByApproverChart/{h_id}", ["uses" => "DashboardController@getRejectionByApproverChart"])->name('dashboard.getRejectionByApproverChart');
		Route::get("/getAverageDurationOfApprovalTimeChart/{h_id}", ["uses" => "DashboardController@getAverageDurationOfApprovalTimeChart"])->name('dashboard.getAverageDurationOfApprovalTimeChart');
		Route::get("/getAverageDurationOfTimeBetweenApproveLogs/{h_id}", ["uses" => "DashboardController@getAverageDurationOfTimeBetweenApproveLogs"])->name('dashboard.getAverageDurationOfTimeBetweenApproveLogs');
		Route::get("compliance/reports/complianceReport/{group_id}", ["uses" => "ComplianceController@getReports"])->name("compliance.complianceReport");
		Route::post("compliance/reports/complianceReport/{group_id}", ["uses" => "ComplianceController@postPhysiciancomplianceReport"]);
		Route::get("compliance/reports/practiceReport/{group_id}", ["uses" => "ComplianceController@getcompliancePracticeReport"])->name("compliance.practiceReport");
		Route::post("compliance/reports/practiceReport/{group_id}", ["uses" => "ComplianceController@postPracticeComplianceReport"]);
		Route::get("compliance/reports/contractTypeReport/{group_id}", ["uses" => "ComplianceController@getcomplianceContractTypeReport"])->name("compliance.contractTypeReport");
		Route::post("compliance/reports/contractTypeReport/{group_id}", ["uses" => "ComplianceController@postContractTypeComplianceReport"]);
		Route::get("/getComplianceAgreementsByHospital/{h_id}/{c_id}", ["uses" => "ComplianceController@getComplianceAgreementsByHospital"])->name('compliance.complianceAgreementsByHospital');
		Route::get('compliance/reports/{report}', ['uses' => 'ComplianceController@downloadReport'])->name('compliance.report');
		Route::get('compliance/reports/{report}/delete', ['uses' => 'ComplianceController@getDeleteReport'])->name('compliance.delete_report');
		Route::get("/getContractTypesForComplianceReport/{h_id}", ["uses" => "ComplianceController@getContractTypesForComplianceReport"])->name('compliance.getContractTypesForComplianceReport');
		Route::get("/getAverageDurationOfPaymentApprovalPopUp/{h_id}/{type_id}", ["uses" => "DashboardController@getAverageDurationOfPaymentApprovalPopUp"])->name('dashboard.getAverageDurationOfPaymentApprovalPopUp');
		Route::get("/getAverageDurationOfProviderApprovalPopUp/{h_id}/{type_id}", ["uses" => "DashboardController@getAverageDurationOfProviderApprovalPopUp"])->name('dashboard.getAverageDurationOfProviderApprovalPopUp');
		
		Route::get("/rehab_admin", ["uses" => "HospitalsController@getDashboardForRehabAdmin"])->name('dashboard.rehab_admin');
		Route::get("/rehab_weekly_max", ["uses" => "HospitalsController@getRehabWeeklyMax"])->name('dashboard.rehab_weekly_max');
		Route::get("/rehab_admin_hours", ["uses" => "HospitalsController@getRehabAdminHours"])->name('dashboard.rehab_admin_hours');
		Route::get("/getWeeklyMaxForSelectedPeriod/{c_id}/{selected_date}", ["uses" => "HospitalsController@getWeeklyMaxForSelectedPeriod"])->name('dashboard.get_weekly_max_hour');
		Route::post("/postWeeklyMaxForSelectedPeriod", ["uses" => "HospitalsController@postWeeklyMaxForSelectedPeriod"])->name('dashboard.post_weekly_max_hour');
		Route::get("/getAdminHours/{c_id}/{selected_date}", ["uses" => "HospitalsController@getAdminHours"])->name('dashboard.get_admin_hour');
		Route::post("/postAdminHours", ["uses" => "HospitalsController@postAdminHours"])->name('dashboard.post_admin_hours');
	});

// ---------------------------------------------------------------------------
// Foreign System Interface Routes
// ---------------------------------------------------------------------------
	
	Route::post('interfaceLawson', ['uses' => 'InterfaceController@lawsonInterface']);
//one-many physician : New script to update practice_id in table 'physician reports' by 1254
	Route::get('oneToManyPhysicianReportUpdate', ['uses' => 'PhysiciansController@onetomanyphysicianreports_update']);
	
	Route::get('updateApproveTime/{h_id}', ['uses' => 'PhysiciansController@update_approved_time_physician_log']);
	Route::get('updatePaymentStatus/{h_id}/{a_id}/{c_id}', ['uses' => 'PhysiciansController@update_payment_status']);
	
	Route::post('agreements/addPaymentAll', ['uses' => 'AgreementsController@addPaymentAll'])->name('agreements.addPaymentAll');
// Attestation 6.1.1.8
	Route::get('/attestations', ['uses' => 'AttestationsController@getAttestation'])->name('attestations.index');
	Route::get('/attestations/create', ['uses' => 'AttestationsController@getCreate'])->name('attestations.create');
	Route::post('/attestations/create', ['uses' => 'AttestationsController@postCreate']);
	Route::get('/attestations/edit/{state_id}/{attestation_id}/{question_id}', ['uses' => 'AttestationsController@getEdit'])->name('question.edit');
	Route::post('/attestations/edit/{state_id}/{attestation_id}/{question_id}', ['uses' => 'AttestationsController@postEdit']);
	Route::get('/attestations/{state_id}/{attestation_id}/{question_id}/delete', ['uses' => 'AttestationsController@getDeleteAttestationQuestion'])->name('attestations.delete_attestation_question');
	Route::get('/attestations/physician/annually/{id}/{c_id}/{selected_date}', ['uses' => 'AttestationsController@getPhysicianAttestation'])->name('attestations.physician');
	Route::get('/getMonthlyPhysicianAttestations/{c_id}', ['uses' => 'AttestationsController@getMonthlyPhysicianAttestations']);
	Route::get('/attestations/physician/monthly/{id}/{c_id}/{selected_date}', ['uses' => 'AttestationsController@getPhysicianMonthlyAttestation'])->name('attestations.physician_monthly');
	Route::get('hospitals/{id}/attestations', ['uses' => 'AttestationsController@getReports'])->name('hospitals.attestation');
	Route::post('hospitals/{id}/attestations', ['uses' => 'AttestationsController@postReports']);
	Route::get('hospitals/{id}/attestations/reports/{report}', ['uses' => 'AttestationsController@downloadReport'])->name('attestation.report');
	Route::get('hospitals/{id}/attestations/reports/{report}/delete', ['uses' => 'AttestationsController@getDeleteReport'])->name('attestation.delete_report');
	Route::get('getAgreements', ['uses' => 'BreakdownController@getAgreements']);

Route::resource('audits', AuditsController::class);

// Mobile Graphs API
Route::post("/api/v3/authenticate", ["uses" => "ApiController@postNewAuthenticate"]);
Route::get("/api/v1/getProviderBynpi", ["uses" => "GraphApiController@getProviderByNPI"]);
Route::get("/api/v1/getCompensationSummaryGuage", ["uses" => "GraphApiController@getCompensationSummaryGuage"]);
Route::get("/api/v1/getCompensationSummary", ["uses" => "GraphApiController@getCompensationSummary"]);
Route::get("/api/v1/getProductivityCompensation", ["uses" => "GraphApiController@getProductivityCompensation"]);
Route::get("/api/v1/getTtmProductivity", ["uses" => "GraphApiController@getTtmProductivity"]);

Route::get("AddPhysicianAndPracticeId/{id}", ["uses" => "ContractsController@AddPhysicianAndPracticeId"]);
Route::get("AddUpdateAmountPaidPhysiciansTable", ["uses" => "ContractsController@AddUpdateAmountPaidPhysiciansTable"]);

// Physician Dashboard
Route::get('getRecentLogs/{p_id}/{c_id}', ['uses' => 'PhysiciansController@getRecentLogs']);
Route::get('getApprovedLogs/{p_id}/{c_id}', ['uses' => 'PhysiciansController@getApprovedLogs']);
Route::post("/api/v4/getRecentLogs", ["uses" => "ApiController@getPhysicianRecentLogs"]);
Route::post("/api/v4/getApprovedLogs", ["uses" => "ApiController@getPhysicianApprovedLogs"]);
Route::post("/api/v4/contracts", ["uses" => "ApiController@getContractsNew"]);
Route::post("/api/v5/contracts", ["uses" => "ApiController@getContractsPerformance"]);
Route::post("/api/v4/priorMonthLogs", ["uses" => "ApiController@getPriorMonthLogsNew"]);
Route::get("/getPendingApprovers/{h_id}", ["uses" => "HospitalsController@getPendingApprovers"]);
Route::post("/api/v1/getContractDetails", ["uses" => "ApiController@getContractDetails"]);
Route::post("/api/v1/getAllRejectedLogs", ["uses" => "ApiController@getAllRejectedLogs"]);

Route::resource('audits', AuditsController::class)->only('index');

// File Routes
Route::get('/file/{fileId}/download', [FileController::class, 'download']);

Route::get('getRecentLogs/{p_id}/{c_id}', ['uses' => 'PhysiciansController@getRecentLogs']);
Route::get('getApprovedLogs/{p_id}/{c_id}', ['uses' => 'PhysiciansController@getApprovedLogs']);
Route::post("/api/v4/getRecentLogs", ["uses" => "ApiController@getPhysicianRecentLogs"]);
Route::post("/api/v4/getApprovedLogs", ["uses" => "ApiController@getPhysicianApprovedLogs"]);
Route::post("/api/v4/contracts", ["uses" => "ApiController@getContractsNew"]);
Route::post("/api/v5/contracts", ["uses" => "ApiController@getContractsPerformance"]);
Route::post("/api/v4/priorMonthLogs", ["uses" => "ApiController@getPriorMonthLogsNew"]);
Route::get("/getPendingApprovers/{h_id}", ["uses" => "HospitalsController@getPendingApprovers"]);

Route::resource('audits', AuditsController::class)->only('index');
Route::get('/file/{fileId}/download', [FileController::class, 'download']);


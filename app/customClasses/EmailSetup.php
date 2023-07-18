<?php
namespace App\customClasses;

use Illuminate\Support\Facades\Log;

class EmailSetup
{
    /**
     * @param $agreement_data
     * @return array
     *
     * Below method is used for calculating the payment frequency date range for weekly contract types logs.
     * It requires agreement_data as parameter for calculation.
     */
    public static $email_setup = [
        'agreementExpiryReminder' => [
            'view' => 'emails.hospitals.agreementExpiryReminder'
        ],
        'reportReminderMail' => [
            'view' => 'emails.hospitals.reportReminderMail'
        ],
        'logApproval' => [
            'view' => 'emails.physicians.logApproval'
        ],
        'approvalReminderMailNextLevel' => [
            'view' => 'emails.hospitals.approvalReminderMailNextLevel'
        ],
        'approvalReminderMailCM' => [
            'view' => 'emails.hospitals.approvalReminderMailCM'
        ],
        'approvalReminderMailFM' => [
            'view' => 'emails.hospitals.approvalReminderMailFM'
        ],
        'approvalReminderMailEM' => [
            'view' => 'emails.hospitals.approvalReminderMailEM'
        ],
        'resetPassword' => [
            'view' => 'emails.physicians.reset_password'
        ],
        'physicianWelcome' => [
            'view' => 'emails.physicians.welcome'
        ],
        'logEntryReminderForPhysicians' => [
            'view' => 'emails.physicians.logEntryReminderForPhysicians'
        ],
        'ticketsCreate' => [
            'view' => 'emails.tickets.create'
        ],
        'ticketsNotification' => [
            'view' => 'emails.tickets.notification'
        ],
        'ticketsMessage' => [
            'view' => 'emails.tickets.message'
        ],
        'ticketsResponse' => [
            'view' => 'emails.tickets.response'
        ],
        'userResetPassword' => [
            'view' => 'emails.users.reset_password'
        ],
        'userWelcome' => [
            'view' => 'emails.users.welcome'
        ],
        'logApprovalFromPM' => [
            'view' => 'emails.physicians.logApprovalFromPM'
        ],
        'addManager' => [
            'view' => 'emails.practices.add_manager'
        ],
        'sendOtp' => [
            'view' => 'emails.physicians.send_otp'
        ],
        'physicianlogApproval' => [
            'view' => 'emails.physicians.logApproval'
        ],
        'logUnApproval' => [
            'view' => 'emails.physicians.logUnApproval'
        ],
        'logReminderForPhysiciansDirectorShip' => [
            'view' => 'emails.physicians.logReminderForPhysiciansDirectorShip'
        ],
        'logReminderForPhysicians' => [
            'view' => 'emails.physicians.logReminderForPhysicians'
        ],
        'emailInvoicePhysician_' => [
            'view' => 'emails.hospitals.emailInvoicePhysician'
        ],
        'emailInvoicePhysician' => [
            'view' => 'emails.hospitals.emailInvoicePhysician'
        ],
        'emailInvoiceWithLog' => [
            'view' => 'emails.hospitals.emailInvoiceWithLog'
        ],
        'proxyApproverAssignment' => [
            'view' => 'emails.users.proxy_approver_assignment'
        ],
        'massEmailer' => [
            'view' => 'emails.dashboard.emailer'
        ],
        'resubmitLogReminderForPhysicians' => [
            'view' => 'emails.physicians.resubmitLogReminderForPhysicians'
        ],
        'usersBackup' => [
            'view' => 'emails.users.backUp'
        ],
        'lawsonFailure' => [
            'view' => 'emails.interface.lawson.failure'
        ],
        'emailLawsonInvoice' => [
            'view' => 'emails.hospitals.emailLawsonInvoice'
        ],
        'approverPendingLog' => [
            'view' => 'emails.approver.approver_pending_logs'
        ],
        'nextApproverPendingLogs' => [
            'view' => 'emails.approver.next_approver_pending_logs'
        ],
        'invoiceReminderRecipientsEmail' => [
            'view' => 'emails.approver.invoice_reminder_recipients'
        ],
        'emailException' => [
            'view' => 'emails.exception'
        ],
        'paymentStatusDashboardReport' => [
            'view' => 'emails.users.payment_status_report'
        ],
        'attestationAlert' => [
            'view' => 'emails.attestation.attestation_alert'
        ],
    ];
    const EMAIL_EXCEPTION_SUPPORT_TEAM = 'emailException';
    const AGREEMENT_EXPIRY_REMINDER = 'agreementExpiryReminder';
    const REPORT_REMINDER_MAIL = 'reportReminderMail';
    const LOG_APPROVAL = 'logApproval';
    const APPROVAL_REMINDER_MAIL_NEXT_LEVEL = 'approvalReminderMailNextLevel';
    const APPROVAL_REMINDER_MAIL_CM = 'approvalReminderMailCM';
    const APPROVAL_REMINDER_MAIL_FM = 'approvalReminderMailFM';
    const APPROVAL_REMINDER_MAIL_EM = 'approvalReminderMailEM';
    const RESET_PASSWORD = 'resetPassword';
    const PHYSICIAN_WELCOME = 'physicianWelcome';
    const LOG_ENTRY_REMINDER_FOR_PHYSICIANS = 'logEntryReminderForPhysicians';
    const TICKETS_CREATE = 'ticketsCreate';
    const TICKETS_NOTIFICATION = 'ticketsNotification';
    const TICKETS_MESSAGE = 'ticketsMessage';
    const TICKETS_RESPONSE = 'ticketsResponse';
    const USER_RESET_PASSWORD = 'userResetPassword';
    const USER_WELCOME = 'userWelcome';
    const LOG_APPROVAL_FROM_PM = 'logApprovalFromPM';
    const ADD_MANAGER = 'addManager';
    const SEND_OTP = 'sendOtp';
    const PHYSICIAN_LOG_APPROVAL = 'physicianlogApproval';
    const LOG_UNAPPROVAL = 'logUnApproval';
    const LOG_REMINDER_FOR_PHYSICIAN_DIRECTORSHIP = 'logReminderForPhysiciansDirectorShip';
    const LOG_REMINDER_FOR_PHYSICIANS = 'logReminderForPhysicians';
    const EMAIL_INVOICE_PHYSICIAN_ = 'emailInvoicePhysician_';
    const EMAIL_INVOICE_PHYSICIAN = 'emailInvoicePhysician';
    const EMAIL_INVOICE_WITH_LOG = 'emailInvoiceWithLog';
    const PROXY_APPROVER_ASSIGNMENT = 'proxyApproverAssignment';
    const MASS_EMAILER = 'massEmailer';
    const RESUBMIT_LOG_REMINDER_FOR_PHYSICIAN = 'resubmitLogReminderForPhysicians';
    const USER_BACKUP = 'usersBackup';
    const LAWSON_FAILURE = 'lawsonFailure';
    const EMAIL_LAWSON_INVOICE = 'emailLawsonInvoice';
    const APPROVER_PENDING_LOG = 'approverPendingLog';
    const NEXT_APPROVER_PENDING_LOG = 'nextApproverPendingLogs';
    const INVOICE_REMINDER_RECIPIENTS_EMAIL = 'invoiceReminderRecipientsEmail';
    const PAYMENT_STATUS_DASHBOARD_REPORT = 'paymentStatusDashboardReport';
    const ATTESTATION_ALERT = 'attestationAlert';

    public static function getEmailTypeData($type) {
        try{
            if(count(self::$email_setup) > 0){
                return self::$email_setup[$type];
            }
        }catch (\Exception $ex){
            Log::info("Email Setup Error" . $ex->getMessage());
        }
    }
}
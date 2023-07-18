<?php namespace App\Console;

use App\Console\Commands\ClearOldEmailActivityLogData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\AdminReportCommand',
		'App\Console\Commands\ApprovalStatusReport',
		'App\Console\Commands\Blank',
		'App\Console\Commands\BreakdownReportCommand',
		'App\Console\Commands\BreakdownReportCommandMultipleMonths',
		'App\Console\Commands\PaymentSummaryReportCommandMultipleMonths',
		'App\Console\Commands\DbConversionCommand',
		'App\Console\Commands\HealthSystemActiveContractsReportCommand',
		'App\Console\Commands\HealthSystemContractsExpiringReportCommand',
		'App\Console\Commands\HealthSystemDashRefreshCommand',
		'App\Console\Commands\HealthSystemProviderProfileReportCommand',
		'App\Console\Commands\HealthSystemSpendYTDEffectivenessReportCommand',
		'App\Console\Commands\HospitalActiveContractsReportCommand',
		'App\Console\Commands\HospitalInvoiceCommand',
		'App\Console\Commands\HospitalLawsonInterfacedInvoiceCommand',
		'App\Console\Commands\HospitalLawsonInterfacedReportCommand',
		'App\Console\Commands\HospitalReportCommand',
		'App\Console\Commands\HospitalReportMedicalDirectorshipContractCommand',
		'App\Console\Commands\OnCallReportCommand',
		'App\Console\Commands\OnCallScheduleCommand',
		'App\Console\Commands\PaymentStatusReport',
		'App\Console\Commands\PhysicianReportCommand',
		'App\Console\Commands\PracticeReportCommand',
		'App\Console\Commands\PracticeReportCommand',
		'App\Console\Commands\CompliancePhysicianReport',
		'App\Console\Commands\CompliancePracticeReport',
		'App\Console\Commands\ComplianceContractTypeReport',
		'App\Console\Commands\PerformancePhysicianReport',
		'App\Console\Commands\ApproverPendingLogEmail',
		'App\Console\Commands\HospitalDashboardRefreshListOfContractSpecifics',
		'App\Console\Commands\HospitalDashboardRefreshPieCharts',
		'App\Console\Commands\HospitalsContractSpendAndPaid',
		'App\Console\Commands\PerformanceApproverReport',
		'App\Console\Commands\ComplianceDashboardRefresh',
		'App\Console\Commands\RunQueueJobs',
		'App\Console\Commands\InvoiceDashboardOnOff',
        'App\Console\Commands\RehabHospitalInvoiceCommand',
        ClearOldEmailActivityLogData::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('healthsystem:regendash')->withoutOverlapping()
			->everyMinute();

		$schedule->command('invoicedashboard:invoicedashboardonoff')->withoutOverlapping()
			->monthlyOn(5, '1:00');

		$schedule->command('sendemail:approverpendinglog')->withoutOverlapping()
			->weeklyOn(1, '5:00');

		$schedule->command('hospitaldashboard:hospitalscontractspendandpaid')->withoutOverlapping()
			->dailyAt('2:00');

		$schedule->command('compliancedashboard:compliancedashboardrefresh')->withoutOverlapping()
			->dailyAt('3:00');

        $schedule->command('admin:clear-old-email-activity-logs 30')->monthlyOn('28', '08:00');
	}

}

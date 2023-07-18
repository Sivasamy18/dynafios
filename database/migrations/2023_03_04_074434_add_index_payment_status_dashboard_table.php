<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexPaymentStatusDashboardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_status_dashboard', function (Blueprint $table) {
            $table->index('physician_id');
            $table->index('practice_id');
            $table->index('contract_id');
            $table->index('contract_name_id');
            $table->index('hospital_id');
            $table->index('period_min_date');
            $table->index('period_max_date');
            $table->index('approved_logs');
            $table->index('pending_logs');
            $table->index('rejected_logs');
            $table->index(['physician_id', 'practice_id', 'contract_id', 'contract_name_id', 'hospital_id', 'period_min_date', 'period_max_date', 'approved_logs', 'pending_logs', 'rejected_logs'], 'payment_status_dashboard_all_fields_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_status_dashboard', function (Blueprint $table) {
            $table->dropIndex('payment_status_dashboard_physician_id_index');
            $table->dropIndex('payment_status_dashboard_practice_id_index');
            $table->dropIndex('payment_status_dashboard_contract_id_index');
            $table->dropIndex('payment_status_dashboard_contract_name_id_index');
            $table->dropIndex('payment_status_dashboard_hospital_id_index');
            $table->dropIndex('payment_status_dashboard_period_min_date_index');
            $table->dropIndex('payment_status_dashboard_period_max_date_index');
            $table->dropIndex('payment_status_dashboard_approved_logs_index');
            $table->dropIndex('payment_status_dashboard_pending_logs_index');
            $table->dropIndex('payment_status_dashboard_rejected_logs_index');
            $table->dropIndex('payment_status_dashboard_all_fields_index');
        });
    }
}

@php use function App\Start\is_super_user; @endphp
<div class="sidebar dashboard-sidebar">
    <div class="table">
        <div class="table-row">
            <div class="table-cell cell-left">
                <img src="{{ asset('assets/img/default/dynafios-app.png') }}" alt="DYNAFIOS App"/>
            </div>
            <div class="table-cell cell-right">
                <ul class="links">
                <h6>
                    <li><a href="{{ URL::to('/assets/pdf/guide.pdf') }}" target="_blank">View the DYNAFIOS Guide</a></li>
                </h6>
                </ul>
            </div>
        </div>
    </div>
    @if (is_super_user())
    <div class="table">
        <div class="table-row">
            <div class="table-cell cell-left">
                <img src="{{ asset('assets/img/default/dynafios-data.png') }}" alt="DYNAFIOS Data"/>
            </div>
            <div class="table-cell cell-right">
                <table class="table data-table">
                    <tr>
                        <td class="dashboard_stats" colspan="2">
                            <h6>Dashboard Data</h6>
                        </td>
                    </tr>
                    <tr>
                        <td class="right">{{ $physicians_page_count }}</td>
                        <td>Physicians</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $practices_page_count }}</td>
                        <td>Practices</td>
                    </tr>
                    <!-- <tr>
                        <td class="right">{{ $users_page_count }}</td>
                        <td>Users</td>
                    </tr>
                    -->
                    <!-- <tr>
                        <td class="right">{{ $hospitals_page_count }}</td>
                        <td>Hospitals</td>
                    </tr>  -->
                    <!-- <tr>
                        <td class="right">{{ $physician_count_exclude_one }}</td>
                        <td>Physicians with Agreements</td>
                    </tr> -->
                    <tr>
                        <td class="right">{{ $hospital_user_count_distinct_exclude_one }}</td>
                        <td>Hospital Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $practice_user_count_distinct_exclude_one }}</td>
                        <td>Practice Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $contract_count_exclude_one }}</td>
                        <td>Contracts</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="table">
        <div class="table-row">
            <div class="table-cell cell-left">
                <img src="{{ asset('assets/img/default/dynafios-users.png') }}" alt="DYNAFIOS Users"/>
            </div>
            <div class="table-cell cell-right">
                <table class="table data-table">
                    <tr>
                        <td class="dashboard_stats" colspan="2">
                            <h6>Dashboard Users</h6>
                        </td>
                    </tr>
                    <tr>
                        <td class="right">{{ $hospital_count_exclude_one }}</td>
                        <td>Billable Hospitals</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $hospital_pending_count_exclude_one }}</td>
                        <td>Pending Hospitals</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $user_distinct_count }}</td>
                        <td>Distinct Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $multi_facility_users }}</td>
                        <td>Multi-facility Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $user_count }}</td>
                        <td>Billable Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $health_system_user_count_exclude_one }}</td>
                        <td>Health-Sys Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $user_pending_count }}</td>
                        <td>Pending Users</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $new_contracts }}</td>
                        <td>New Contracts</td>
                    </tr>
                    <tr>
                        <td class="right">{{ $online_count }}</td>
                        <td>Online Users</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
        <div class="table">
            <div class="table-row">
                <div class="table-cell cell-left">
                    <!--<img src="{{ asset('assets/img/default/dynafios-users.png') }}" alt="DYNAFIOS Users"/>-->
                </div>
                <div class="table-cell cell-right">
                    @foreach($dash as $dashbord_statitics)
                        <table class="table data-table">
                            <tr>
                                <td class="dashboard_stats" colspan="2">
                                    <h6>{{ $dashbord_statitics['contract_type_name'] }}</h6>
                                </td>
                            </tr>
                            <tr>
                                <td class="right">{{ $dashbord_statitics['hospital_counts'] }}</td>
                                <td>Hospitals</td>
                            </tr>
                            <tr>
                                <td class="right">{{ $dashbord_statitics['physician_counts'] }}</td>
                                <td>Physicians</td>
                            </tr>
                            <tr>
                                <td class="right">{{ $dashbord_statitics['hospital_user_counts'] }}</td>
                                <td>Hospital Users</td>
                            </tr>
                            <tr>
                                <td class="right">{{ $dashbord_statitics['practice_user_counts'] }}</td>
                                <td>Practice Users</td>
                            </tr>
                        </table>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    <div class="table">
        <div class="table-row">
            <div class="table-cell cell-left">
                <img src="{{ asset('assets/img/default/dynafios-support.png') }}" alt="DYNAFIOS Support"/>
            </div>
            <div class="table-cell cell-right">
                <table class="table data-table">
                    <tr>
                        <td class="dashboard_stats" colspan="2">
                            <h6>Need Help?</h6>
                        </td>
                    </tr>
                </table>
                <p>
                    Submit a ticket now using the DYNAFIOS dashboard help center. Alternatively contact the Dynafios
                    support team at <a href="mailto:support@dynafiosapp.com">support@dynafiosapp.com</a>.
                </p>
                <a class="btn btn-default btn-sm" href="{{ URL::route('tickets.index') }}">
                    <i class="fa fa-question-circle fa-fw"></i> Help Center
                </a>
            </div>
        </div>
    </div>
    <div class="table">
        <div class="table-row">
            <div class="table-cell cell-left">
            </div>
            <div class="table-cell cell-right">
                <table class="table data-table">
                    <tr>
                        <td class="dashboard_stats" colspan="2">
                            <h6>Download the DYNAFIOS App</h6>
                        </td>
                    </tr>
                </table>
                <div class="text-center">
                    <a href="https://play.google.com/store/apps/details?id=dynafios.trace" target="_blank">
                        <img src="{{ asset('assets/img/default/play-icon.png') }}" alt="Google Play"/>
                    </a>
                    <a href="https://itunes.apple.com/us/app/trace-by-dynafios/id657793782?mt=8" target="_blank">
                        <img src="{{ asset('assets/img/default/appstore-icon.png') }}" alt="Apple App Store"/>
                    </a>
                </div>
            </div>
        </div>
    </div>
    </ul>
</div>
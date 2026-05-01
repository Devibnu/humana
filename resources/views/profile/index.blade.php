@extends('layouts.user_type.auth')

@section('content')

<div class="row">
    <div class="col-12 mb-4">
        <div class="card mx-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="bg-gradient-dark p-4 position-relative">
                    <div class="row align-items-center">
                        <div class="col-lg-8 d-flex align-items-center gap-3 flex-wrap">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $user->name }} avatar" class="avatar avatar-xxl border-radius-xl shadow-sm object-fit-cover">
                            @else
                                <div class="avatar avatar-xxl border-radius-xl bg-white d-flex align-items-center justify-content-center shadow-sm">
                                    <span class="text-dark text-xl font-weight-bold">{{ $avatarInitials }}</span>
                                </div>
                            @endif
                            <div>
                                <p class="text-uppercase text-xs text-white-50 mb-1">Humana HRIS Profile</p>
                                <h3 class="text-white mb-1">{{ $user->name }}</h3>
                                <p class="text-white-50 mb-2">{{ $user->email }}</p>
                                <span class="badge {{ $roleBadgeClass }}">{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}</span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                            <a href="{{ route('user-profile.edit') }}" class="btn bg-gradient-light btn-sm mb-0">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-header pb-0">
                <h6 class="mb-0">Account Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <p class="text-xs text-uppercase text-secondary mb-1">Full Name</p>
                    <h6 class="mb-0">{{ $user->name }}</h6>
                </div>
                <div class="mb-3">
                    <p class="text-xs text-uppercase text-secondary mb-1">Email</p>
                    <h6 class="mb-0">{{ $user->email }}</h6>
                </div>
                <div class="mb-3">
                    <p class="text-xs text-uppercase text-secondary mb-1">Role</p>
                    <span class="badge {{ $roleBadgeClass }}">{{ $user->roleName() ?? ucfirst(str_replace('_', ' ', (string) $user->roleKey())) }}</span>
                </div>
                @if ($user->phone)
                <div class="mb-3">
                    <p class="text-xs text-uppercase text-secondary mb-1">Phone</p>
                    <h6 class="mb-0">{{ $user->phone }}</h6>
                </div>
                @endif
                @if ($user->location)
                <div class="mb-3">
                    <p class="text-xs text-uppercase text-secondary mb-1">Location</p>
                    <h6 class="mb-0">{{ $user->location }}</h6>
                </div>
                @endif
                @if ($user->about_me)
                <div>
                    <p class="text-xs text-uppercase text-secondary mb-1">About</p>
                    <p class="text-sm mb-0">{{ $user->about_me }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card mx-4 h-100">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Employee Information</h6>
                @if ($employee)
                    <span class="badge bg-gradient-success">Linked</span>
                @else
                    <span class="badge bg-gradient-secondary">Not Linked</span>
                @endif
            </div>
            <div class="card-body">
                @if ($employee)
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <p class="text-xs text-uppercase text-secondary mb-1">NIK</p>
                            <h6 class="mb-0">{{ $employee->employee_code }}</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="text-xs text-uppercase text-secondary mb-1">Employee Status</p>
                            <span class="badge bg-gradient-info">{{ ucfirst($employee->status) }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="text-xs text-uppercase text-secondary mb-1">Department</p>
                            <h6 class="mb-0">{{ $employee->department?->name ?? '-' }}</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="text-xs text-uppercase text-secondary mb-1">Position</p>
                            <h6 class="mb-0">{{ $employee->position?->name ?? '-' }}</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="text-xs text-uppercase text-secondary mb-1">Work Location</p>
                            <h6 class="mb-0">{{ $employee->workLocation?->name ?? '-' }}</h6>
                        </div>
                        <div class="col-md-6 mb-3">
                            <p class="text-xs text-uppercase text-secondary mb-1">Employee Email</p>
                            <h6 class="mb-0">{{ $employee->email }}</h6>
                        </div>
                    </div>
                @else
                    <div class="border border-secondary border-radius-md p-3 bg-gray-100">
                        <p class="text-sm font-weight-bold mb-1">Employee data not linked yet</p>
                        <p class="text-sm text-secondary mb-0">This account is active, but no employee master record is attached yet. HR can link this user to an employee profile to unlock department, position, work location, attendance, and leave summaries.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($user->isEmployee() || $user->isManager())
<div class="row">
    <div class="col-12">
        <div class="card mx-4 mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">Personal Attendance and Leave Summary</h6>
                    <p class="text-sm mb-0">Weekly personal HR activity summary.</p>
                </div>
                @if ($weeklyAttendanceSummary)
                    <span class="badge bg-gradient-light text-dark">{{ $weeklyAttendanceSummary['label'] }}</span>
                @endif
            </div>
            <div class="card-body">
                @if ($weeklyAttendanceSummary)
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="border border-success border-radius-md p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Present</p>
                                <h4 class="text-success mb-0">{{ $weeklyAttendanceSummary['present'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="border border-secondary border-radius-md p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Absent</p>
                                <h4 class="text-dark mb-0">{{ $weeklyAttendanceSummary['absent'] }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-info border-radius-md p-3 h-100">
                                <p class="text-xs text-uppercase text-secondary mb-1">Leave This Week</p>
                                <h4 class="text-info mb-0">{{ $weeklyAttendanceSummary['leave'] }}</h4>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="border border-secondary border-radius-md p-3 bg-gray-100">
                        <p class="text-sm font-weight-bold mb-1">Attendance summary unavailable</p>
                        <p class="text-sm text-secondary mb-0">Your account does not have a linked employee record yet, so personal attendance and leave summary cannot be calculated.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection
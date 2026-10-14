@extends('layouts.app')
@section('title','Home Stay')
@section('content')
<div class="container mt-4">
    <h3 class="mb-4">Home Stay</h3>
    <div class="row g-4">
        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack fs-1 text-primary"></i>
                    <h5 class="mt-3">
                        <a href="{{ route('pendingRent.list') }}" class="text-decoration-none text-dark">
                            Pending Rent
                        </a>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1 text-success"></i>
                    <h5 class="mt-3"></h5>
                    <h5 class="mt-3">
                        <a href="{{ route('studentDetails.list') }}" class="text-decoration-none text-dark">
                            Student Details
                        </a>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-house-door fs-1 text-danger"></i>
                    <h5 class="mt-3">
                        <a href="{{ route('bedOccupancy.list') }}" class="text-decoration-none text-dark">
                            Bed Status
                        </a>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-person-plus fs-1 text-warning"></i>
                    <h5 class="mt-3">
                        <a href="{{ route('newStudent.list') }}" class="text-decoration-none text-dark">
                            New Student
                        </a>
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .dashboard-card {
        border-radius: 15px;
        transition: .3s;
        cursor: pointer;
    }

    .dashboard-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
    }

    .dashboard-card .card-body {
        padding: 40px 20px;
    }

    .dashboard-card i {
        font-size: 55px;
    }

    .dashboard-card h5 {
        margin-top: 15px;
        font-weight: 600;
    }
</style>
@endsection
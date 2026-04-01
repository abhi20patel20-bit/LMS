<?php

use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseSessionController;
use App\Http\Controllers\JobRoleController;
use App\Http\Controllers\JobFamilyController;
use App\Http\Controllers\MatricesController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\LmsMeController;
use App\Http\Controllers\LmsBookingController;
use App\Http\Controllers\LmsMyPlaceController;
use App\Http\Controllers\LmsMatricesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users'         => (int) User::count(),
        'roles'         => (int) Role::count(),
        'permissions'   => (int) Permission::count(),
    ]);
})->middleware(['auth', 'verified', 'check.suspension', 'can:read users'])->name('dashboard');

Route::middleware('auth', 'verified', 'check.suspension')->group(function () {

    // User Management
    Route::post('/users/suspend', [UserController::class, 'suspend'])->middleware('can:update users')->name('user.suspend');
    Route::get('/users/restore/{id}', [UserController::class, 'restore'])->middleware('can:update users')->name('user.restore');
    Route::get('/get-users', [UserController::class, 'getUsersByRule'])->middleware('can:read users')->name('user.getUsersByRule');
    Route::resource('/user', UserController::class)->middleware('can:read users')->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->middleware('can:delete users')->name('user.destroy-bulk');
    Route::get('/get-user/{id}', [UserController::class, 'getUser'])->middleware('can:read users')->name('user.getUser');

    // Permissions and Roles
    Route::get('/get-roles-dropdown', [RoleController::class, 'getRolesDropdown'])->middleware('can:read roles')->name('role.getRolesDropdown');
    Route::get('/get-roles', [RoleController::class, 'getRoles'])->middleware('can:read roles')->name('role.getRoles');
    Route::get('/get-permissions', [PermissionController::class, 'getAllPermissions'])->middleware('can:read permissions')->name('permission.getAllPermissions');
    Route::resource('/role', RoleController::class)->middleware('can:read roles')->except('create', 'show', 'edit');
    Route::resource('/permission', PermissionController::class)->middleware('can:read permissions')->except('create', 'show', 'edit');

    // Company Management
    Route::get('/get-companies-dropdown', [CompanyController::class, 'getCompaniesDropdown'])->name('company.getCompaniesDropdown');
    Route::get('/get-companies', [CompanyController::class, 'getCompanies'])->name('company.getCompanies');
    Route::resource('/company', CompanyController::class)->except('create', 'show', 'edit');
    Route::post('/company-update/{id}', [CompanyController::class, 'update'])->name('company.update');

    // Course management
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/get-courses', [CourseController::class, 'getCourses']);

    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

    Route::prefix('/courses/{course}')->middleware('can:update courses')->group(function () {
        Route::get('/sessions', [CourseSessionController::class, 'index']);
        Route::post('/sessions', [CourseSessionController::class, 'store']);
        Route::put('/sessions/{session}', [CourseSessionController::class, 'update']);
        Route::delete('/sessions/{session}', [CourseSessionController::class, 'destroy']);
    });

    // optional restore
    // Route::get('/courses/restore/{id}', [CourseController::class, 'restore']);

    // Course Category management
    Route::get('/course-categories', [CourseCategoryController::class, 'index']);
    Route::get('/get-course-categories', [CourseCategoryController::class, 'getCourseCategories']);
    Route::get('/course-categories/{id}', [CourseCategoryController::class, 'show']);
    Route::post('/course-categories', [CourseCategoryController::class, 'store']);
    Route::put('/course-categories/{id}', [CourseCategoryController::class, 'update']);
    Route::delete('/course-categories/{id}', [CourseCategoryController::class, 'destroy']);

    // Provider management
    Route::get('/providers', [ProviderController::class, 'index']);
    Route::get('/get-providers', [ProviderController::class, 'getProviders']);
    Route::get('/providers/{id}', [ProviderController::class, 'show']);
    Route::post('/providers', [ProviderController::class, 'store']);
    Route::put('/providers/{id}', [ProviderController::class, 'update']);
    Route::delete('/providers/{id}', [ProviderController::class, 'destroy']);

    // Matrices
    Route::get('/matrices', [MatricesController::class, 'index']);
    Route::get('/get-matrix-job-roles', [MatricesController::class, 'getJobRoles']);
    Route::get('/get-matrix-courses', [MatricesController::class, 'getRequiredCourses']);

    // User LMS pages
    Route::get('/lms/dashboard', function () {
        return Inertia::render('Lms/Dashboard');
    })->middleware('can:read user dashboard');
    Route::get('/lms/learning', function () {
        return Inertia::render('Lms/Learning');
    })->middleware('can:read my learning');
    Route::get('/lms/metrics', function () {
        return Inertia::render('Lms/Metrics');
    })->middleware('can:read metrics');

    Route::get('/lms/my-place', [LmsMyPlaceController::class, 'index'])
        ->middleware('can:read my learning');

    // User LMS endpoints
    Route::prefix('lms/me')->group(function () {
        Route::get('/dashboard', [LmsMeController::class, 'dashboard']);
        Route::get('/learning', [LmsMeController::class, 'learning']);
        Route::post('/courses/{course}/enroll', [LmsMeController::class, 'enroll']);
        Route::post('/courses/{course}/start', [LmsMeController::class, 'start']);
        Route::post('/courses/{course}/complete', [LmsMeController::class, 'complete']);
        Route::post('/courses/{course}/cancel', [LmsMeController::class, 'cancel']);
    });

    Route::prefix('lms/me')->middleware('can:read my learning')->group(function () {
        Route::get('/courses/{course}/booking/metadata', [LmsBookingController::class, 'metadata']);
        Route::get('/courses/{course}/booking/dates', [LmsBookingController::class, 'dates']);
        Route::get('/courses/{course}/booking/sessions', [LmsBookingController::class, 'sessions']);
        Route::post('/courses/{course}/booking', [LmsBookingController::class, 'book']);
        Route::post('/courses/{course}/booking/update', [LmsBookingController::class, 'updateBooking']);
        Route::post('/courses/{course}/waitlist', [LmsBookingController::class, 'joinWaitlist']);
        Route::post('/bookings/{booking}/cancel', [LmsBookingController::class, 'cancelBooking']);
    });

    Route::prefix('lms/me')->middleware('can:read my learning')->group(function () {
        Route::get('/my-place/filters', [LmsMyPlaceController::class, 'filters']);
        Route::get('/my-place/categories', [LmsMyPlaceController::class, 'categories']);
        Route::get('/my-place/job-roles', [LmsMyPlaceController::class, 'jobRoles']);
        Route::get('/my-place/courses', [LmsMyPlaceController::class, 'courses']);
        Route::get('/courses/{course}', [LmsMyPlaceController::class, 'course']);
    });

    Route::prefix('lms/me/matrices')->middleware('can:read metrics')->group(function () {
        Route::get('/filters', [LmsMatricesController::class, 'filters']);
        Route::get('/categories', [LmsMatricesController::class, 'categories']);
        Route::get('/job-roles', [LmsMatricesController::class, 'jobRoles']);
        Route::get('/courses', [LmsMatricesController::class, 'courses']);
    });

    Route::prefix('me')->group(function () {
        Route::post('/courses/{course}/start', [LmsMeController::class, 'start']);
        Route::post('/courses/{course}/complete', [LmsMeController::class, 'complete']);
        Route::post('/courses/{course}/cancel', [LmsMeController::class, 'cancel']);
    });

    // Job Role management
    Route::get('/job-roles', [JobRoleController::class, 'index']);
    Route::get('/get-job-roles', [JobRoleController::class, 'getJobRoles']);
    Route::get('/job-roles/{id}', [JobRoleController::class, 'show']);
    Route::post('/job-roles', [JobRoleController::class, 'store']);
    Route::put('/job-roles/{id}', [JobRoleController::class, 'update']);
    Route::delete('/job-roles/{id}', [JobRoleController::class, 'destroy']);

    // Job Family management
    Route::get('/job-families', [JobFamilyController::class, 'index']);
    Route::get('/get-job-families', [JobFamilyController::class, 'getJobFamilies']);
    Route::get('/job-families/{id}', [JobFamilyController::class, 'show']);
    Route::post('/job-families', [JobFamilyController::class, 'store']);
    Route::put('/job-families/{id}', [JobFamilyController::class, 'update']);
    Route::delete('/job-families/{id}', [JobFamilyController::class, 'destroy']);

    // Department Management
    Route::resource('/department', DepartmentController::class)->except('create', 'edit');
    Route::get('/get-departments', [DepartmentController::class, 'getDepartments']);


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/user', UserController::class)->middleware('can:read users')->except('create', 'show', 'edit');
    Route::post('/user/destroy-bulk', [UserController::class, 'destroyBulk'])->middleware('can:delete users')->name('user.destroy-bulk');
    Route::resource('/permission', PermissionController::class)->middleware('can:read permissions')->except('create', 'show', 'edit');

});

Route::get('/form', function () {
    return Inertia::render('SakaiForm');
});

Route::get('/button', function () {
    return Inertia::render('SakaiButton');
});

Route::get('/list', function () {
    return Inertia::render('SakaiList');
});

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';

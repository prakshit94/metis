{{-- ══ TAB: Overview / Profile ══ --}}
<div x-show="activeTab === 'overview'" class="security-section" x-cloak>
    <h5>Personal Information</h5>
    <p>Manage customer personal profile and contact details</p>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Full Name</h6>
            <small>{{ $customer->name }}</small>
        </div>
        <button type="button" @click="$dispatch('open-modal', { name: 'edit-profile-modal' })" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil-square me-1"></i> Edit Profile
        </button>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Email Address</h6>
            <small>{{ $customer->email ?: 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Phone Number</h6>
            <small>{{ $customer->phone ?: 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Alternate Mobile</h6>
            <small>{{ $customer->alternatemobile ?? 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Relative Contact</h6>
            <small>{{ ($customer->relative_name ?? 'Not provided') . ($customer->relative_phone ? " ({$customer->relative_phone})" : '') }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Customer ID</h6>
            <small>#{{ sprintf('%04d', $customer->id) }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Account Status</h6>
            <small>{{ ucfirst($customer->status) }}</small>
        </div>
    </div>
    
    <h5 class="mt-5">Tax & Business Info</h5>
    <p>View business identifiers and tax registration details</p>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Company Name</h6>
            <small>{{ $customer->company_name ?? 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>GST Number</h6>
            <small>{{ $customer->gst_no ?: 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>PAN Number</h6>
            <small>{{ $customer->pan_no ?: 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Aadhaar Last4</h6>
            <small>{{ $customer->aadhaar_last4 ?? 'Not provided' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Category & Source</h6>
            <small>{{ ucfirst($customer->category ?? 'Individual') }} • {{ is_array($customer->source) ? implode(', ', $customer->source) : ($customer->source ?: 'Direct') }}</small>
        </div>
    </div>
    
    <h5 class="mt-5">Agriculture Profile</h5>
    <p>View land capacity, crops, and irrigation details</p>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Land Area</h6>
            <small>{{ $customer->land_area ?? 0 }} {{ $customer->land_unit ?? 'Acre' }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Irrigation Type</h6>
            <small>{{ is_array($customer->irrigation_type) ? implode(', ', $customer->irrigation_type) : ($customer->irrigation_type ?: 'Not provided') }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Major Crops Cultivated</h6>
            <small>
                @forelse($customer->crops ?? [] as $crop)
                    {{ $crop }}@if(!$loop->last), @endif
                @empty
                    No crops recorded
                @endforelse
            </small>
        </div>
    </div>
    
    <h5 class="mt-5">Quick Stats</h5>
    <p>Overview of customer orders and activity</p>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Total Orders</h6>
            <small>{{ $customer->orders()->count() }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Total Addresses</h6>
            <small>{{ $customer->addresses->count() }}</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Credit Limit</h6>
            <small>₹ {{ number_format($customer->credit_limit ?? 0) }}</small>
        </div>
    </div>
    
    <h5 class="mt-5">System Info</h5>
    <p>Internal tracking and timeline</p>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Created At</h6>
            <small>{{ $customer->created_at->format('M d, Y — h:i A') }} ({{ $customer->created_at->diffForHumans() }})</small>
        </div>
    </div>
    
    <div class="security-item">
        <div class="security-info">
            <h6>Last Updated</h6>
            <small>{{ $customer->updated_at->format('M d, Y — h:i A') }} ({{ $customer->updated_at->diffForHumans() }})</small>
        </div>
    </div>

</div>

@extends('layouts.vertical', ['title' => 'Color Trading Investment', 'subTitle' => 'Bot'])

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .countdown-timer {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        border: 2px solid #90caf9;
        border-radius: 12px;
        padding: 1rem;
    }
    .countdown-value {
        font-size: 2rem;
        font-weight: bold;
        color: #1976d2;
        line-height: 1;
    }
    .countdown-label {
        font-size: 0.75rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .timer-completed {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
        border-color: #28a745 !important;
    }
    .timer-urgent {
        animation: pulse 2s infinite;
        border-color: #ffc107 !important;
    }
    .timer-critical {
        animation: pulse 1s infinite;
        border-color: #dc3545 !important;
    }
    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
        70% { transform: scale(1.02); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .investment-card {
        transition: all 0.2s ease;
        border-radius: 12px;
    }
    .investment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .return-amount {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .progress-24h {
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }
    
    @media (max-width: 768px) {
        .countdown-value {
            font-size: 1.5rem;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        .countdown-timer {
            padding: 0.75rem;
        }
    }
    
    @media (max-width: 576px) {
        .countdown-value {
            font-size: 1.25rem;
        }
        .return-amount {
            font-size: 1rem;
        }
    }
</style>
@endpush

@section('content')

@php
    // Calculate balance directly from crypto wallets
    $totalBalance = \App\Models\CryptoWallet::where('user_id', $user->id)
        ->where('is_active', 1)
        ->sum('balance');
    
    if ($totalBalance == 0) {
        $totalBalance = \App\Models\CryptoWallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->sum('balance');
    }
    
    if ($totalBalance == 0) {
        $wallets = \App\Models\CryptoWallet::where('user_id', $user->id)->get();
        foreach ($wallets as $wallet) {
            if ($wallet->is_active) {
                $totalBalance += floatval($wallet->balance);
            }
        }
    }
    
    $hasBalance = $totalBalance > 0;
@endphp

<div class="row">
    <div class="col-12">
        <!-- Game Account Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h4 class="card-title d-flex align-items-center mb-2">
                            <iconify-icon icon="material-symbols:palette" class="me-2 text-primary"></iconify-icon>
                            Color Trading Investment
                        </h4>
                        <p class="text-muted small mb-0">
                            Game Account: <span class="fw-medium text-success">{{ $user->profile->uname ?? 'Not Linked' }}</span>
                            @if($user->profile && $user->profile->umoney)
                            | Game Balance: <span class="fw-medium text-success">${{ number_format($user->profile->umoney, 2) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success d-inline-flex align-items-center">
                            <span class="badge-dot bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></span>
                            Connected
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <iconify-icon icon="material-symbols:more-vert" class="me-1"></iconify-icon>
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="refreshGameBalance()">
                                    <iconify-icon icon="material-symbols:refresh" class="me-2"></iconify-icon>
                                    Refresh Balance
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('bot.index') }}">
                                    <iconify-icon icon="material-symbols:home" class="me-2"></iconify-icon>
                                    Back to Games
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="unlinkAccount()">
                                    <iconify-icon icon="material-symbols:link-off" class="me-2"></iconify-icon>
                                    Unlink Account
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investment Interface -->
        <div class="row g-4">
            <!-- Platform Wallet Balance -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="material-symbols:account-balance-wallet" class="me-2"></iconify-icon>
                            Platform Wallet Balance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="text-{{ $hasBalance ? 'success' : 'muted' }} mb-1">
                                ${{ number_format($totalBalance, 2) }}
                            </h3>
                            <p class="text-muted small mb-0">Available Balance</p>
                        </div>

                        @if($hasBalance && $investmentData['can_invest'])
                            <div class="row g-2 mb-3">
                                @if($investmentData['minimum_amount'] <= $totalBalance)
                                    <div class="col-6">
                                        <button class="btn btn-outline-primary w-100 btn-sm" onclick="quickInvest({{ $investmentData['minimum_amount'] }})">
                                            <iconify-icon icon="material-symbols:trending-up" class="me-1"></iconify-icon>
                                            Min: ${{ number_format($investmentData['minimum_amount'], 0) }}
                                        </button>
                                    </div>
                                @endif
                                @if(100 >= $investmentData['minimum_amount'] && 100 <= min($investmentData['maximum_amount'], $totalBalance))
                                    <div class="col-6">
                                        <button class="btn btn-outline-primary w-100 btn-sm" onclick="quickInvest(100)">
                                            <iconify-icon icon="material-symbols:trending-up" class="me-1"></iconify-icon>
                                            $100
                                        </button>
                                    </div>
                                @endif
                                @if(500 >= $investmentData['minimum_amount'] && 500 <= min($investmentData['maximum_amount'], $totalBalance))
                                    <div class="col-6">
                                        <button class="btn btn-outline-primary w-100 btn-sm" onclick="quickInvest(500)">
                                            <iconify-icon icon="material-symbols:trending-up" class="me-1"></iconify-icon>
                                            $500
                                        </button>
                                    </div>
                                @endif
                                @if(min($investmentData['maximum_amount'], $totalBalance) > $investmentData['minimum_amount'])
                                    <div class="col-6">
                                        <button class="btn btn-outline-success w-100 btn-sm" onclick="quickInvest({{ min($investmentData['maximum_amount'], $totalBalance) }})">
                                            <iconify-icon icon="material-symbols:trending-up" class="me-1"></iconify-icon>
                                            Max: ${{ number_format(min($investmentData['maximum_amount'], $totalBalance), 0) }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-3">
                                <iconify-icon icon="material-symbols:account-balance-wallet" class="fs-1 text-muted mb-3"></iconify-icon>
                                @if(!$hasBalance)
                                    <h6 class="text-muted mb-2">No Balance Available</h6>
                                    <p class="text-muted small mb-3">Add funds to your wallet to start investing</p>
                                    <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-primary btn-sm">
                                        <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                                        Add Funds
                                    </a>
                                @else
                                    <h6 class="text-muted mb-2">Investment Not Available</h6>
                                    <p class="text-muted small mb-3">{{ $investmentData['message'] }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="border-top pt-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-success w-100 btn-sm">
                                        <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                                        Deposit
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('wallets.withdraw.wallet') }}" class="btn btn-warning w-100 btn-sm">
                                        <iconify-icon icon="material-symbols:remove" class="me-1"></iconify-icon>
                                        Withdraw
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Investment Panel -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="material-symbols:trending-up" class="me-2"></iconify-icon>
                            Investment Plan
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($investmentData['can_invest'])
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-center">
                                    <iconify-icon icon="material-symbols:info" class="me-2"></iconify-icon>
                                    <div>
                                        <h6 class="mb-1">{{ $investmentData['plan']->name }}</h6>
                                        @if($investmentData['tier'])
                                            <small class="mb-1 d-block">
                                                <strong>{{ $investmentData['tier_name'] }}</strong> - 
                                                {{ $investmentData['interest_rate'] }}% {{ ucfirst($investmentData['plan']->interest_type) }}
                                            </small>
                                        @else
                                            <small class="mb-1 d-block">
                                                {{ $investmentData['interest_rate'] }}% {{ ucfirst($investmentData['plan']->interest_type) }}
                                            </small>
                                        @endif
                                        <small class="text-muted">
                                            Range: ${{ number_format($investmentData['minimum_amount'], 2) }} - 
                                            ${{ number_format($investmentData['maximum_amount'], 2) }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <form id="investmentForm">
                                @csrf
                                <div class="mb-4">
                                    <label for="investAmount" class="form-label fw-medium">Investment Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input 
                                            type="number" 
                                            class="form-control" 
                                            id="investAmount" 
                                            name="amount"
                                            placeholder="0.00"
                                            min="{{ $investmentData['minimum_amount'] }}" 
                                            max="{{ min($investmentData['maximum_amount'], $totalBalance) }}" 
                                            step="0.01"
                                            {{ !$hasBalance ? 'disabled' : '' }}
                                        >
                                    </div>
                                    <div class="form-text">
                                        Min: ${{ number_format($investmentData['minimum_amount'], 2) }} | 
                                        Max: ${{ number_format(min($investmentData['maximum_amount'], $totalBalance), 2) }} |
                                        Available: ${{ number_format($totalBalance, 2) }}
                                    </div>
                                </div>

                                <div class="d-grid mb-3">
                                    <button 
                                        type="submit" 
                                        class="btn btn-primary btn-lg" 
                                        id="investBtn"
                                        {{ !$hasBalance ? 'disabled' : '' }}
                                    >
                                        <span id="investText">Invest</span>
                                    </button>
                                </div>

                                <div class="alert alert-success">
                                    <iconify-icon icon="material-symbols:info" class="me-2"></iconify-icon>
                                    <small>
                                        Your investment will earn {{ $investmentData['interest_rate'] }}% returns {{ $investmentData['plan']->interest_type }}.
                                    </small>
                                </div>
                            </form>
                        @else
                            <div class="text-center py-4">
                                <iconify-icon icon="material-symbols:block" class="fs-1 text-warning mb-3"></iconify-icon>
                                <h6 class="text-muted mb-2">Investment Plan Not Available</h6>
                                <p class="text-muted small mb-3">{{ $investmentData['message'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Investments - Full Width Responsive -->
        @if($investmentStats['active_investments']->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="material-symbols:trending-up" class="me-2"></iconify-icon>
                    Active Investments
                    <span class="badge bg-success ms-2">{{ $investmentStats['active_investments']->count() }}</span>
                </h5>
            </div>
            
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">Plan</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Earned</th>
                                    <th scope="col">Next Return</th>
                                    <th scope="col">24H Progress</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($investmentStats['active_investments'] as $investment)
                                    @php
                                        $nextReturnDate = $investment->getNextReturnDueDate();
                                        $isOverdue = $nextReturnDate && now()->isAfter($nextReturnDate);
                                        $singleReturn = $investment->calculateSingleReturn();
                                        
                                        // Calculate 24-hour progress - FIXED LOGIC
                                        $lastReturnDate = $investment->last_return_at ?? $investment->started_at;
                                        $hoursSinceLastReturn = $lastReturnDate->diffInHours(now());
                                        $progress24h = min(100, ($hoursSinceLastReturn / 24) * 100);
                                        // Ensure we never show negative progress
                                        $progress24h = max(0, $progress24h);
                                    @endphp
                                    
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $investment->investmentPlan->name }}</div>
                                            <small class="text-muted">{{ $investment->investmentPlan->interest_type }} returns</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary">{{ $investment->formatted_amount }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">{{ $investment->formatted_paid_return }}</div>
                                        </td>
                                        <td>
                                            @if($investment->investmentPlan->interest_type === 'daily' && $nextReturnDate)
                                                @if($isOverdue)
                                                    <span class="badge bg-warning text-dark">Processing</span>
                                                @else
                                                    <div class="countdown-display" 
                                                         data-target-time="{{ $nextReturnDate->toISOString() }}"
                                                         data-investment-id="{{ $investment->id }}">
                                                        <div class="d-flex gap-1 align-items-center">
                                                            <span class="countdown-hours">00</span>:
                                                            <span class="countdown-minutes">00</span>:
                                                            <span class="countdown-seconds">00</span>
                                                        </div>
                                                        <div class="return-amount text-success small">
                                                            +${{ number_format($singleReturn, 2) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="progress progress-24h">
                                                <div class="progress-bar bg-info" style="width: {{ $progress24h }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ round($progress24h) }}%</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Active</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none p-3">
                    @foreach($investmentStats['active_investments'] as $investment)
                        @php
                            $nextReturnDate = $investment->getNextReturnDueDate();
                            $isOverdue = $nextReturnDate && now()->isAfter($nextReturnDate);
                            $singleReturn = $investment->calculateSingleReturn();
                            
                            // Calculate 24-hour progress - FIXED LOGIC
                            $lastReturnDate = $investment->last_return_at ?? $investment->started_at;
                            $hoursSinceLastReturn = $lastReturnDate->diffInHours(now());
                            $progress24h = min(100, ($hoursSinceLastReturn / 24) * 100);
                            // Ensure we never show negative progress
                            $progress24h = max(0, $progress24h);
                        @endphp
                        
                        <div class="investment-card border mb-3">
                            <div class="card-body">
                                {{-- Header --}}
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $investment->investmentPlan->name }}</h6>
                                        <small class="text-muted">{{ ucfirst($investment->investmentPlan->interest_type) }} Returns</small>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>

                                {{-- Stats Grid --}}
                                <div class="stats-grid mb-3">
                                    <div class="text-center">
                                        <div class="fw-bold text-primary">{{ $investment->formatted_amount }}</div>
                                        <small class="text-muted">Invested</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="fw-bold text-success">{{ $investment->formatted_paid_return }}</div>
                                        <small class="text-muted">Earned</small>
                                    </div>
                                </div>

                                {{-- 24-Hour Timer Section --}}
                                @if($investment->investmentPlan->interest_type === 'daily' && $nextReturnDate)
                                    <div class="border-top pt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-semibold">Next Return</span>
                                            @if($isOverdue)
                                                <span class="badge bg-warning text-dark">Processing</span>
                                            @else
                                                <span class="badge bg-info">Due Soon</span>
                                            @endif
                                        </div>
                                        
                                        @if($isOverdue)
                                            <div class="alert alert-warning py-2 mb-2">
                                                <iconify-icon icon="material-symbols:schedule" class="me-1"></iconify-icon>
                                                <small>Return processing...</small>
                                            </div>
                                        @else
                                            {{-- Mobile Countdown Timer --}}
                                            <div class="countdown-timer text-center" 
                                                 data-target-time="{{ $nextReturnDate->toISOString() }}"
                                                 data-investment-id="{{ $investment->id }}">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-4">
                                                        <div class="countdown-value countdown-hours">00</div>
                                                        <div class="countdown-label">Hours</div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="countdown-value countdown-minutes">00</div>
                                                        <div class="countdown-label">Minutes</div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="countdown-value countdown-seconds">00</div>
                                                        <div class="countdown-label">Seconds</div>
                                                    </div>
                                                </div>
                                                <div class="return-amount text-success">
                                                    Next Return: ${{ number_format($singleReturn, 2) }}
                                                </div>
                                            </div>
                                        @endif
                                        
                                        {{-- 24-Hour Progress --}}
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">24H Progress</small>
                                                <small class="text-muted">{{ round($progress24h) }}%</small>
                                            </div>
                                            <div class="progress progress-24h">
                                                <div class="progress-bar bg-info" style="width: {{ $progress24h }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@section('script')
<script>
let countdownTimers = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeInvestmentForm();
    initializeCountdownTimers();
});

function initializeInvestmentForm() {
    const form = document.getElementById('investmentForm');
    const investBtn = document.getElementById('investBtn');
    const investText = document.getElementById('investText');
    const amountInput = document.getElementById('investAmount');

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const amount = parseFloat(amountInput.value);
            if (!amount || amount <= 0) {
                showAlert('Please enter a valid investment amount', 'error');
                return;
            }

            const minAmount = {{ $investmentData['can_invest'] ? $investmentData['minimum_amount'] : 0 }};
            const maxAmount = {{ $investmentData['can_invest'] ? min($investmentData['maximum_amount'], $totalBalance) : 0 }};

            if (amount < minAmount) {
                showAlert(`Minimum investment amount is $${minAmount.toFixed(2)}`, 'error');
                return;
            }

            if (amount > maxAmount) {
                showAlert(`Maximum investment amount is $${maxAmount.toFixed(2)}`, 'error');
                return;
            }

            setLoadingState(true);

            try {
                const response = await fetch('{{ route("bot.color-trading.invest") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ amount: amount })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(`Successfully invested $${amount.toFixed(2)}!`, 'success');
                    form.reset();
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showAlert(result.message || 'Investment failed. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Investment error:', error);
                showAlert('Network error occurred. Please try again.', 'error');
            } finally {
                setLoadingState(false);
            }
        });
    }
}

function initializeCountdownTimers() {
    const timers = document.querySelectorAll('[data-target-time]');
    
    timers.forEach(timer => {
        const targetTime = new Date(timer.dataset.targetTime);
        const investmentId = timer.dataset.investmentId;
        
        const intervalId = setInterval(() => {
            updateCountdown(timer, targetTime, investmentId);
        }, 1000);
        
        countdownTimers.push(intervalId);
        updateCountdown(timer, targetTime, investmentId);
    });
}

function updateCountdown(timerElement, targetTime, investmentId) {
    const now = new Date();
    const diff = targetTime - now;
    
    if (diff <= 0) {
        handleTimerCompletion(timerElement, investmentId);
        return;
    }
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    const hoursElement = timerElement.querySelector('.countdown-hours');
    const minutesElement = timerElement.querySelector('.countdown-minutes');
    const secondsElement = timerElement.querySelector('.countdown-seconds');
    
    if (hoursElement) hoursElement.textContent = hours.toString().padStart(2, '0');
    if (minutesElement) minutesElement.textContent = minutes.toString().padStart(2, '0');
    if (secondsElement) secondsElement.textContent = seconds.toString().padStart(2, '0');
    
    // Add visual urgency
    if (hours === 0) {
        timerElement.classList.add('timer-urgent');
        if (minutes < 5) {
            timerElement.classList.remove('timer-urgent');
            timerElement.classList.add('timer-critical');
        }
    }
}

function handleTimerCompletion(timerElement, investmentId) {
    timerElement.classList.add('timer-completed');
    timerElement.classList.remove('timer-urgent', 'timer-critical');
    
    timerElement.innerHTML = `
        <div class="text-center">
            <iconify-icon icon="material-symbols:check-circle" class="fs-2 text-success mb-2"></iconify-icon>
            <div class="fw-bold text-success">Return Due!</div>
            <small class="text-muted">Processing...</small>
        </div>
    `;
    
    showAlert('Daily return is now due and being processed!', 'success');
    
    setTimeout(() => {
        checkReturnStatus(investmentId);
    }, 1000);
}

function checkReturnStatus(investmentId) {
    const url = '{{ route("bot.investment.return-status", ":investmentId") }}'.replace(':investmentId', investmentId);
    fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.return_processed) {
            showAlert('Return has been credited to your balance!', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            setTimeout(() => checkReturnStatus(investmentId), 30000);
        }
    })
    .catch(error => console.error('Error checking return status:', error));
}

function quickInvest(amount) {
    const amountInput = document.getElementById('investAmount');
    if (amountInput) {
        amountInput.value = amount;
        document.getElementById('investmentForm').dispatchEvent(new Event('submit'));
    }
}

function setLoadingState(loading) {
    const investBtn = document.getElementById('investBtn');
    const investText = document.getElementById('investText');
    const amountInput = document.getElementById('investAmount');

    if (investBtn && investText) {
        if (loading) {
            investBtn.disabled = true;
            investText.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            if (amountInput) amountInput.disabled = true;
        } else {
            investBtn.disabled = false;
            investText.innerHTML = 'Invest';
            if (amountInput) amountInput.disabled = false;
        }
    }
}

async function refreshGameBalance() {
    try {
        const response = await fetch('{{ route("bot.api.refresh-balance") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.success) {
            showAlert(`Game balance refreshed: $${result.data.balance}`, 'success');
        } else {
            showAlert(result.message || 'Failed to refresh balance', 'error');
        }
    } catch (error) {
        console.error('Refresh error:', error);
        showAlert('Failed to refresh balance', 'error');
    }
}

function unlinkAccount() {
    if (!confirm('Are you sure you want to unlink your game account?')) return;

    fetch('{{ route("bot.color-trading.unlink") }}', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('Account unlinked successfully!', 'success');
            setTimeout(() => {
                window.location.href = '{{ route("bot.color-trading") }}';
            }, 2000);
        } else {
            showAlert(result.message || 'Failed to unlink account', 'error');
        }
    })
    .catch(error => {
        console.error('Unlink error:', error);
        showAlert('Network error occurred', 'error');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 5000);
}

window.addEventListener('beforeunload', function() {
    countdownTimers.forEach(timer => clearInterval(timer));
});
</script>
@endsection
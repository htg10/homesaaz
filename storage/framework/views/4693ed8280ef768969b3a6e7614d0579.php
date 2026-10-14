<?php $__env->startSection('title', 'HomeSaaz | Login'); ?>
<?php $__env->startSection('body_class', 'hs-login-page'); ?>

<?php $__env->startSection('content'); ?>
<div class="hs-card">
    <div class="hs-logo">
        <img src="<?php echo e(asset('assets/images/homesaaz-logo.png')); ?>" alt="HomeSaaz">
    </div>

    
    <div class="hs-actions" id="hsActions">
        <button type="button" class="hs-btn hs-btn--login" id="hsLoginBtn">Login</button>

        
        <a href="#" class="hs-btn hs-btn--supplier" data-todo="supplier">Supplier Request</a>

        
        <a href="#" class="hs-btn hs-btn--customer" data-todo="customer">Customer Request</a>

        
        <button type="button" class="hs-btn hs-btn--extra" disabled><span>Coming soon</span></button>
    </div>

    
    <div class="hs-login-form" id="hsLoginForm">
        <div class="hs-form-title">Login</div>
        
        <?php if(session()->has('login_phone')): ?>
            
            <p class="hs-otp-note">OTP sent to ******<?php echo e(substr(session('login_phone'), -4)); ?></p>
            <?php if(session('debug_otp')): ?>
                <div style="background:#fff3cd;border:1px solid #ffe08a;padding:8px;border-radius:6px;margin-bottom:10px;font-size:13px;">
                    <strong>TEST MODE (local only):</strong> OTP = <strong><?php echo e(session('debug_otp')); ?></strong>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('login.otp.verify')); ?>" autocomplete="off">
                <?php echo csrf_field(); ?>
                <div class="hs-field">
                    <label>OTP <sup>*</sup></label>
                    <input type="text" name="otp" inputmode="numeric" pattern="[0-9]*"
                           maxlength="<?php echo e(config('auth_otp.otp_length', 6)); ?>" autocomplete="one-time-code" required>
                </div>
                <div class="hs-row">
                    <a href="<?php echo e(route('login')); ?>">Change number</a>
                    <a href="<?php echo e(route('login')); ?>" onclick="event.preventDefault(); document.getElementById('hsResendForm').submit();">Resend OTP</a>
                </div>
                <button type="submit" class="hs-submit">VERIFY &amp; LOGIN</button>
            </form>
            <form method="POST" action="<?php echo e(route('login.otp.send')); ?>" id="hsResendForm" style="display:none">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="phone" value="<?php echo e(session('login_phone')); ?>">
            </form>
        <?php else: ?>
            
            <form method="POST" action="<?php echo e(route('login.otp.send')); ?>" autocomplete="off">
                <?php echo csrf_field(); ?>
                <div class="hs-field">
                    <label>Mobile Number <sup>*</sup></label>
                    <input type="tel" name="phone" value="<?php echo e(old('phone')); ?>" inputmode="numeric"
                           maxlength="15" autocomplete="off" required>
                </div>
                <button type="submit" class="hs-submit">SEND OTP</button>
            </form>
        <?php endif; ?>
        <button type="button" class="hs-back" id="hsBackBtn">&larr; Back</button>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\homeSaaz-complete\homeSaaz\resources\views/auth/login.blade.php ENDPATH**/ ?>
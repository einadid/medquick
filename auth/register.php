<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../classes/Auth.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$registrationSuccess = false;
$generatedMemberId = null;
$registeredEmail = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }

    $auth = new Auth();
    $result = $auth->register($_POST, $_POST['verification_code'] ?? null);

    if ($result['success']) {
        $registrationSuccess = true;
        $generatedMemberId = $result['member_id'] ?? null;
        $registrationMessage = $result['message'];
        $registeredEmail = $_POST['email'];
        header("refresh:5;url=" . SITE_URL . "/auth/login.php");
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Register';
require_once '../includes/header.php';
?>

<style>
/* Compact Register Page - Green Theme */
.register-wrapper {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
}

.register-container {
    max-width: 1000px;
    width: 100%;
    background: white;
    box-shadow: var(--shadow-xl);
    display: flex;
    border-left: 6px solid var(--accent-gold);
}

/* Left Branding - Compact */
.register-brand {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
    padding: 2rem;
    color: white;
    flex: 0 0 280px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.brand-logo-icon {
    font-size: 2.5rem;
    background: var(--accent-gold);
    padding: 0.5rem;
}

.brand-logo-text {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0;
}

.brand-tagline {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--light-green);
}

.brand-features {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
    font-size: 0.875rem;
}

.brand-feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.brand-feature-icon {
    background: var(--accent-gold);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}

/* Right Form Section - Compact */
.register-form-section {
    flex: 1;
    padding: 2rem;
}

.form-header {
    margin-bottom: 1.25rem;
}

.form-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--primary-green);
    margin-bottom: 0.25rem;
}

.form-subtitle {
    color: var(--text-medium);
    font-size: 0.875rem;
}

.alert-box {
    padding: 0.875rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--error);
    background: #fee2e2;
    color: #991b1b;
    font-size: 0.875rem;
}

.member-preview {
    background: linear-gradient(135deg, var(--light-green) 0%, var(--secondary-green) 100%);
    border: 2px solid var(--secondary-green);
    border-left: 4px solid var(--primary-green);
    padding: 0.75rem;
    margin-bottom: 1rem;
}

.member-preview-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.375rem;
}

.member-preview-id {
    font-family: 'Courier New', monospace;
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary-dark);
    padding: 0.375rem 0.5rem;
    background: white;
    border: 1px solid var(--primary-green);
}

/* Compact 2-Column Form */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.875rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 0.875rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-weight: 600;
    color: var(--primary-green);
    margin-bottom: 0.375rem;
    font-size: 0.8125rem;
}

.form-label i {
    font-size: 0.875rem;
}

.form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 2px solid var(--bg-gray);
    background: white;
    transition: var(--transition);
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 2px rgba(45, 106, 79, 0.1);
}

textarea.form-input {
    resize: vertical;
    min-height: 60px;
}

.form-help {
    font-size: 0.6875rem;
    color: var(--text-light);
    margin-top: 0.25rem;
}

.form-actions {
    margin-top: 1.25rem;
}

.divider {
    text-align: center;
    margin: 1rem 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 1px;
    background: var(--bg-gray);
}

.divider span {
    background: white;
    padding: 0 0.75rem;
    position: relative;
    color: var(--text-light);
    font-weight: 600;
    font-size: 0.75rem;
}

.footer-text {
    text-align: center;
    color: var(--text-medium);
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
}

.back-link {
    text-align: center;
    margin-top: 1rem;
}

.back-link a {
    color: var(--text-medium);
    font-size: 0.8125rem;
}

/* Success Screen - Compact */
.success-box {
    max-width: 550px;
    width: 100%;
    background: white;
    padding: 2rem;
    box-shadow: var(--shadow-xl);
    text-align: center;
}

.success-icon {
    display: inline-flex;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
    color: white;
    font-size: 3rem;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    from { transform: scale(0); }
    to { transform: scale(1); }
}

.success-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--primary-green);
    margin-bottom: 0.75rem;
}

.success-message {
    font-size: 1rem;
    color: var(--text-medium);
    margin-bottom: 1.5rem;
}

.member-card {
    background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
    color: white;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 6px solid var(--accent-gold);
}

.member-card-header {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.75rem;
}

.member-id-display {
    font-family: 'Courier New', monospace;
    font-size: 1.5rem;
    font-weight: 800;
    background: white;
    color: var(--primary-green);
    padding: 0.75rem;
    margin-bottom: 0.75rem;
}

.member-card-footer {
    font-size: 0.875rem;
}

.credentials {
    background: #fef3c7;
    border: 2px solid var(--warning);
    border-left: 4px solid var(--warning);
    padding: 1rem;
    margin-bottom: 1.5rem;
    text-align: left;
}

.credentials-title {
    font-weight: 700;
    margin-bottom: 0.75rem;
    font-size: 0.9375rem;
}

.credential-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    font-size: 0.875rem;
}

.credential-item:last-child {
    border-bottom: none;
}

.redirect-box {
    background: linear-gradient(135deg, var(--light-green), var(--secondary-green));
    border: 2px solid var(--primary-green);
    padding: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.redirect-icon {
    font-size: 1.5rem;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.redirect-text strong {
    display: block;
    color: var(--primary-dark);
    font-size: 0.9375rem;
}

.redirect-text p {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--text-medium);
}

/* Responsive */
@media (max-width: 768px) {
    .register-container {
        flex-direction: column;
        border-left: none;
        border-top: 6px solid var(--accent-gold);
    }
    
    .register-brand {
        flex: none;
        padding: 1.5rem;
    }
    
    .register-form-section {
        padding: 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .register-wrapper {
        padding: 0.5rem;
    }
    
    .register-form-section {
        padding: 1rem;
    }
    
    .form-title {
        font-size: 1.5rem;
    }
    
    .success-box {
        padding: 1.5rem 1rem;
    }
}
</style>

<?php if ($registrationSuccess): ?>
<!-- SUCCESS SCREEN - COMPACT -->
<div class="register-wrapper">
    <div class="success-box fade-in">
        
        <div class="success-icon">✓</div>
        
        <h2 class="success-title">REGISTRATION SUCCESSFUL!</h2>
        <p class="success-message"><?php echo clean($registrationMessage); ?></p>
        
        <?php if ($generatedMemberId): ?>
        <div class="member-card">
            <div class="member-card-header">
                <i class="fas fa-id-card"></i> YOUR MEMBER ID CARD
            </div>
            <div class="member-id-display">
                <?php echo clean($generatedMemberId); ?>
            </div>
            <div class="member-card-footer">
                🎉 BONUS: <strong><?php echo SIGNUP_BONUS_POINTS; ?> POINTS ADDED!</strong>
            </div>
        </div>
        
        <div class="credentials">
            <div class="credentials-title">📝 Save Your Login Details</div>
            <div class="credential-item">
                <span><strong>Email:</strong></span>
                <span><?php echo clean($registeredEmail); ?></span>
            </div>
            <div class="credential-item">
                <span><strong>Member ID:</strong></span>
                <span style="font-family: monospace; font-weight: 700;"><?php echo clean($generatedMemberId); ?></span>
            </div>
            <div class="credential-item">
                <span><strong>Points:</strong></span>
                <span style="color: var(--primary-green); font-weight: 700;">💰 <?php echo SIGNUP_BONUS_POINTS; ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="redirect-box">
            <div class="redirect-icon">🔄</div>
            <div class="redirect-text">
                <strong>Redirecting to Login...</strong>
                <p>Auto-redirect in 5 seconds</p>
            </div>
        </div>
        
        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary btn-block">
            <i class="fas fa-sign-in-alt"></i> LOGIN NOW
        </a>
        
        <div class="back-link">
            <a href="<?php echo SITE_URL; ?>/index.php">← Back to Homepage</a>
        </div>
        
    </div>
</div>

<?php else: ?>
<!-- REGISTRATION FORM - COMPACT 2-COLUMN -->
<div class="register-wrapper">
    <div class="register-container">
        
        <!-- Left Branding - Compact -->
        <div class="register-brand">
            <div class="brand-logo">
                <span class="brand-logo-icon">💊</span>
                <h1 class="brand-logo-text">QuickMed</h1>
            </div>
            <h2 class="brand-tagline">Join QuickMed Today!</h2>
            
            <div class="brand-features">
                <div class="brand-feature">
                    <span class="brand-feature-icon">✓</span>
                    <span>Get <?php echo SIGNUP_BONUS_POINTS; ?> bonus points</span>
                </div>
                <div class="brand-feature">
                    <span class="brand-feature-icon">✓</span>
                    <span>Unique Member ID</span>
                </div>
                <div class="brand-feature">
                    <span class="brand-feature-icon">✓</span>
                    <span>Earn points on purchase</span>
                </div>
                <div class="brand-feature">
                    <span class="brand-feature-icon">✓</span>
                    <span>Exclusive discounts</span>
                </div>
                <div class="brand-feature">
                    <span class="brand-feature-icon">✓</span>
                    <span>Fast home delivery</span>
                </div>
            </div>
        </div>
        
        <!-- Right Form - Compact 2-Column -->
        <div class="register-form-section">
            
            <div class="form-header">
                <h2 class="form-title">CREATE ACCOUNT</h2>
                <p class="form-subtitle">Sign up for QuickMed account</p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="alert-box">
                <strong>⚠️ Error!</strong> <?php echo clean($error); ?>
            </div>
            <?php endif; ?>
            
            <!-- Member ID Preview -->
            <div class="member-preview">
                <div class="member-preview-label">💡 Member ID Preview:</div>
                <div class="member-preview-id" id="memberIdPreview">(Enter email)</div>
            </div>
            
            <form method="POST">
                <?php echo csrfField(); ?>
                
                <!-- 2-Column Grid -->
                <div class="form-grid">
                    <!-- Email (Full Width) -->
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input type="email" name="email" id="emailInput" required placeholder="yourname@example.com" class="form-input">
                        <small class="form-help">Member ID will be based on this</small>
                    </div>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Full Name *
                        </label>
                        <input type="text" name="full_name" required placeholder="Your full name" class="form-input">
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Phone *
                        </label>
                        <input type="text" name="phone" required placeholder="01XXXXXXXXX" pattern="[0-9]{11}" class="form-input">
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Password *
                        </label>
                        <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" class="form-input">
                    </div>
                    
                    <!-- Verification Code -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-ticket-alt"></i> Code (Optional)
                        </label>
                        <input type="text" name="verification_code" placeholder="qm-xxxxx-xx" class="form-input">
                    </div>
                    
                    <!-- Address (Full Width) -->
                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Address (Optional)
                        </label>
                        <textarea name="address" placeholder="Your delivery address" class="form-input" rows="2"></textarea>
                    </div>
                </div>
                
                <!-- Submit -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> CREATE ACCOUNT
                    </button>
                </div>
            </form>
            
            <div class="divider"><span>OR</span></div>
            
            <p class="footer-text">Already have an account?</p>
            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-outline btn-block">
                <i class="fas fa-sign-in-alt"></i> LOGIN
            </a>
            
            <div class="back-link">
                <a href="<?php echo SITE_URL; ?>/index.php">← Back to Homepage</a>
            </div>
            
        </div>
        
    </div>
</div>

<?php endif; ?>

<script>
// Live Member ID Preview
document.getElementById('emailInput')?.addEventListener('input', function() {
    const email = this.value;
    const preview = document.getElementById('memberIdPreview');
    
    if (email.includes('@')) {
        const username = email.split('@')[0];
        const clean = username.toLowerCase().replace(/[^a-z0-9._-]/g, '');
        preview.textContent = clean || '(invalid)';
        preview.style.fontWeight = '800';
    } else {
        preview.textContent = '(Enter valid email)';
        preview.style.fontWeight = '600';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../classes/Auth.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    $roleRedirect = [
        'admin' => '/admin/index.php',
        'shop_manager' => '/manager/index.php',
        'salesman' => '/salesman/index.php',
        'customer' => '/index.php'
    ];
    redirect($roleRedirect[$user['role_name']] ?? '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $auth = new Auth();
    $result = $auth->login($_POST['email'], $_POST['password']);
    
    if ($result['success']) {
        $roleRedirect = [
            'admin' => '/admin/index.php',
            'shop_manager' => '/manager/index.php',
            'salesman' => '/salesman/index.php',
            'customer' => '/index.php'
        ];
        setFlash('success', 'Login successful! Welcome back.');
        redirect($roleRedirect[$result['role']] ?? '/index.php');
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Login';
require_once '../includes/header.php';
?>

<style>
.auth-page-wrapper {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.auth-container {
    max-width: 1100px;
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: white;
    box-shadow: var(--shadow-xl);
    overflow: hidden;
}

.auth-branding {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-dark) 100%);
    padding: 3rem;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border-right: 6px solid var(--accent-gold);
}

.auth-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.auth-logo-icon {
    font-size: 3rem;
    background: var(--accent-gold);
    padding: 0.75rem;
}

.auth-logo-text {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin: 0;
    text-transform: uppercase;
}

.auth-tagline {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.auth-description {
    font-size: 1rem;
    line-height: 1.7;
    margin-bottom: 2rem;
    opacity: 0.95;
}

.auth-features {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.auth-feature {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.feature-icon {
    background: var(--accent-gold);
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.875rem;
}

.auth-form-section {
    padding: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.auth-form-wrapper {
    width: 100%;
    max-width: 420px;
}

.auth-form-header {
    margin-bottom: 2rem;
    text-align: center;
}

.auth-form-title {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-green);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
}

.auth-form-subtitle {
    color: var(--text-medium);
}

.auth-alert {
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.auth-alert-error {
    background: #fee2e2;
    border-color: var(--error);
    color: #991b1b;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--primary-green);
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    border: 2px solid var(--bg-gray);
    background: white;
    transition: var(--transition);
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.1);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.link-forgot {
    color: var(--primary-green);
    font-weight: 600;
}

.auth-divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.auth-divider::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 1px;
    background: var(--bg-gray);
}

.auth-divider span {
    background: white;
    padding: 0 1rem;
    position: relative;
    color: var(--text-light);
    font-weight: 600;
}

.auth-footer-text {
    color: var(--text-medium);
    margin-bottom: 1rem;
    text-align: center;
}

.auth-back-home {
    margin-top: 2rem;
    text-align: center;
}

.link-back {
    color: var(--text-medium);
    font-size: 0.875rem;
}

@media (max-width: 968px) {
    .auth-container {
        grid-template-columns: 1fr;
    }
    .auth-branding {
        border-right: none;
        border-bottom: 6px solid var(--accent-gold);
    }
}
</style>

<div class="auth-page-wrapper">
    <div class="auth-container">
        
        <div class="auth-branding">
            <div class="auth-logo">
                <span class="auth-logo-icon">💊</span>
                <h1 class="auth-logo-text">QuickMed</h1>
            </div>
            <h2 class="auth-tagline">Your Trusted Pharmacy Partner</h2>
            <p class="auth-description">
                Access your account to browse medicines, manage orders, and enjoy exclusive member benefits.
            </p>
            
            <div class="auth-features">
                <div class="auth-feature">
                    <span class="feature-icon">✓</span>
                    <span>Compare prices across shops</span>
                </div>
                <div class="auth-feature">
                    <span class="feature-icon">✓</span>
                    <span>Earn loyalty points</span>
                </div>
                <div class="auth-feature">
                    <span class="feature-icon">✓</span>
                    <span>Fast home delivery</span>
                </div>
                <div class="auth-feature">
                    <span class="feature-icon">✓</span>
                    <span>Track your orders</span>
                </div>
            </div>
        </div>
        
        <div class="auth-form-section">
            <div class="auth-form-wrapper">
                
                <div class="auth-form-header">
                    <h2 class="auth-form-title">Welcome Back</h2>
                    <p class="auth-form-subtitle">Login to your QuickMed account</p>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="auth-alert auth-alert-error">
                    <strong>⚠️ Error!</strong>
                    <p><?php echo clean($error); ?></p>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" name="email" required placeholder="Enter your email" class="form-input" autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <input type="password" name="password" required placeholder="Enter your password" class="form-input" minlength="6">
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="link-forgot">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i> LOGIN NOW
                    </button>
                </form>
                
                <div class="auth-divider"><span>OR</span></div>
                
                <p class="auth-footer-text">Don't have an account?</p>
                <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-outline btn-block">
                    <i class="fas fa-user-plus"></i> CREATE NEW ACCOUNT
                </a>
                
                <div class="auth-back-home">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="link-back">
                        ← Back to Homepage
                    </a>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
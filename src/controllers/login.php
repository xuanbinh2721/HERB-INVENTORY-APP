<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $u = trim($_POST['username']??''); $p = (string)($_POST['password']??'');
    if(attempt_login($u,$p)){ redirect('/?page=dashboard'); }
    $error = "Sai tài khoản hoặc mật khẩu";
}

$title='Đăng nhập';
include __DIR__ . '/../views/layout/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <div class="mb-3">
                <i class="fas fa-leaf fa-3x text-primary"></i>
              </div>
              <h3 class="fw-bold text-dark mb-2">Herb Inventory</h3>
              <p class="text-muted">Hệ thống quản lý kho </p>
            </div>
            
            <?php if(!empty($error)): ?>
              <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= h($error) ?>
              </div>
            <?php endif; ?>
            
            <form method="post" class="needs-validation" novalidate>
              <div class="mb-3">
                <label class="form-label fw-semibold">
                  <i class="fas fa-user me-2"></i>Tài khoản
                </label>
                <input type="text" class="form-control form-control-lg" name="username" required 
                       placeholder="Nhập tên đăng nhập">
                <div class="invalid-feedback">
                  Vui lòng nhập tên đăng nhập
                </div>
              </div>
              
              <div class="mb-4">
                <label class="form-label fw-semibold">
                  <i class="fas fa-lock me-2"></i>Mật khẩu
                </label>
                <input type="password" class="form-control form-control-lg" name="password" required 
                       placeholder="Nhập mật khẩu">
                <div class="invalid-feedback">
                  Vui lòng nhập mật khẩu
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
              </button>
            </form>
            
            <div class="text-center">
              <div class="alert alert-info mb-0">
                <small class="text-muted">
                  <i class="fas fa-info-circle me-1"></i>
                  Tài khoản mặc định: <code><?= h(getenv('ADMIN_USER')?:'admin') ?></code> / 
                  <code><?= h(getenv('ADMIN_PASS')?:'admin123') ?></code>
                </small>
              </div>
            </div>
          </div>
        </div>
        
        <div class="text-center mt-4">
          <p class="text-white-50 mb-0">
            <small>&copy; 2024 Herb Inventory System. All rights reserved.</small>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Form validation
(function() {
  'use strict';
  window.addEventListener('load', function() {
    var forms = document.getElementsByClassName('needs-validation');
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>

<?php include __DIR__ . '/../views/layout/footer.php'; ?>

</main>

<!-- Footer -->
<footer class="bg-dark text-light py-4 mt-5">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <p class="mb-0">
          <i class="fas fa-leaf me-2"></i>
          <strong>Herb Inventory System</strong> - Hệ thống quản lý kho
        </p>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="mb-0">
          <small class="text-muted">
            &copy; <?= date('Y') ?> Herb Inventory. All rights reserved.
          </small>
        </p>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      if (!alert.classList.contains('alert-danger')) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
          alert.remove();
        }, 500);
      }
    });
  }, 5000);
});

// Confirm delete actions
document.addEventListener('DOMContentLoaded', function() {
  var deleteButtons = document.querySelectorAll('.btn-delete');
  deleteButtons.forEach(function(button) {
    button.addEventListener('click', function(e) {
      if (!confirm('Bạn có chắc chắn muốn xóa mục này?')) {
        e.preventDefault();
      }
    });
  });
});

// Tooltip initialization
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

</body>
</html>

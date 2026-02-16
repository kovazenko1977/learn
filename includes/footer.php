    </main>
    <div id="toast"></div>
    <div id="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p style="margin-top:12px; font-weight:600;">Загрузка...</p>
    </div>
    <script>
      lucide.createIcons();

      document.addEventListener('submit', function() {
          document.getElementById('loading').style.display = 'flex';
      });

      function showToast(message) {
          const toast = document.getElementById('toast');
          toast.textContent = message;
          toast.classList.add('show');
          setTimeout(() => {
              toast.classList.remove('show');
          }, 3000);
      }
    </script>
</body>
</html>

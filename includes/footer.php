    </main>
    <div id="toast"></div>
    <script>
      lucide.createIcons();

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

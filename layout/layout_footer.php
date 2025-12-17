            </div>
        </main>
    </div>

    <script>
        // Detect page show from back/forward cache
        window.addEventListener("pageshow", function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Global back handler
        function goBack() {
            if (document.referrer) {
                window.history.back();
            } else {
                window.location.href = "../home/home.php";
            }
        }
    </script>
    <?php if (isset($additionalScripts) && is_array($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($pageScripts)): ?>
        <?php echo $pageScripts; ?>
    <?php endif; ?>
</body>
</html>

<?php $restaurantInfo = load_restaurant_info(); ?>
    </main>

    <footer class="site-footer">
        <div>
            <strong><?= e($restaurantInfo['name']) ?></strong>
            <span>Ресторан восточнославянской кухни</span>
        </div>
        <div>
            <span>Адрес: <?= e($restaurantInfo['address']) ?></span>
            <span>Телефон: <?= e($restaurantInfo['phone']) ?></span>
        </div>
    </footer>
</div>
</body>
</html>

<header>
    <nav class="main-nav">
        <a class="logo" href="index.php"><img src="../img/Pizzeria.png" alt="Pizzeria logo" /></a>

        <div class="nav-links">
            <?= renderCartButton(getCartAmount()) ?>

            <?= renderAccountButton(isSignedIn()) ?>

        </div>
    </nav>
</header>
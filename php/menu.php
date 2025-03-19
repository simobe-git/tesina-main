<?php
$current_page = basename($_SERVER['PHP_SELF']);

// array contenente le voci di menu per ogni ruolo
$menu_base = [
    'home.php' => 'Home',
    'catalogo.php' => 'Catalogo',
    'offerte.php' => 'Offerte',
    'faq.php' => 'FAQ',
    'contatti.php' => 'Contatti'
];

$menu_cliente = [
    'home.php' => 'Home',
    'catalogo.php' => 'Catalogo',
    'offerte.php' => 'Offerte',
    'carrello.php' => 'Carrello',
    'faq.php' => 'FAQ',
    'contatti.php' => 'Contatti',
    'profilo.php' => 'Profilo'
];

$menu_admin = [
    'home.php' => 'Home',
    'admin_dashboard.php' => 'Dashboard',
    'faq.php' => 'FAQ',
    'contatti.php' => 'Contatti'
];

$menu_gestore = [
    'home.php' => 'Home',
    'gestore_dashboard.php' => 'Dashboard',
    'faq.php' => 'FAQ',
    'contatti.php' => 'Contatti'
];


// determiniamo quale menu mostrare
$menu_items = $menu_base; // per efault: menu base
if (isset($_SESSION['statoLogin'])) {
    if (isset($_SESSION['tipo_utente'])) {
        
        if ($_SESSION['tipo_utente'] === 'cliente') {
            $menu_items = $menu_cliente;
        } elseif ($_SESSION['tipo_utente'] === 'admin') {
            $menu_items = $menu_admin;
        }elseif ($_SESSION['tipo_utente'] === 'gestore') {
            $menu_items = $menu_gestore;
        }
    }
}
?>

<nav class="navbar">
    <div class="logo">
        <a href="home.php">GameShop</a>
    </div>
    <ul class="nav-links">
        <?php
        // mostriamo le voci di menu, escludendo la pagina corrente
        foreach ($menu_items as $page => $label) {
            if ($page !== $current_page) {
                echo "<li><a href=\"$page\">$label</a></li>";
            }
        }

        // aggiungimo opzioni comuni basate sullo stato di login
        if (isset($_SESSION['statoLogin'])) {
            echo "<li><a href=\"logout.php\">Logout</a></li>";
        } else {
            echo "<li><a href=\"login.php\">Login</a></li>";
        }
        ?>
    </ul>
    <div class="hamburger-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav>
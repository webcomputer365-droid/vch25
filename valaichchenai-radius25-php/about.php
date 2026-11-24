<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$activeNav = 'about';
$pageTitle = 'About Radius25 and Valaichchenai';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">A coastal day on the East Coast</span>
        <h1>Make room for the sea.</h1>
        <p>Valaichchenai sits between lagoon and ocean — a base for beaches, viewpoints, heritage waterworks and slow coastal days, all within 25 km.</p>
    </div>
</section>
<section class="section"><div class="container prose">
    <span class="eyebrow">About Radius25</span>
    <h2 style="font:600 clamp(30px,4vw,44px)/1.1 'Playfair Display',serif">A thoughtful day starts with the coast.</h2>
    <p>Radius25 is a small travel guide for Valaichchenai and the surrounding East Coast: Pasikuda and Kalkudah beaches, Elephant Rock, lagoon viewpoints, the Pandarakattu irrigation landmarks, and quieter local places between them.</p>
    <p>The planner helps you sketch a realistic one-day route from Valaichchenai town centre, with pace options, estimated drive times and daylight-aware finish times. Estimates, not promises — check local conditions and make your own choices.</p>
    <div class="planner-callout spacer-top"><div><span class="eyebrow">A respectful visit</span><h2>Travel gently.</h2><p>Beach and lagoon environments are shared with local fishers and wildlife. Take your litter home, respect local advice on swimming, dress modestly near villages and irrigation sites, and give yourself time in the strong coastal sun.</p></div><a class="button button-primary" href="<?= e(url('')) ?>">Explore places <span aria-hidden="true">→</span></a></div>
    <div class="detail-section spacer-top"><h2>About the information here</h2><p>Place details and coordinates are starting points for the project. Opening hours, entry fees, access rules and facilities can change. Confirm them with the relevant site or local authority before travelling.</p></div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
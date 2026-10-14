<?php $__env->startSection('title', 'HomeSaaz | Dashboard'); ?>
<?php $__env->startSection('body_class', 'hs-dashboard-page'); ?>

<?php $__env->startSection('content'); ?>
<?php
// Only the modules this user may open (admin sees all) - see config/user_access.php
$tiles = array_map(function ($tile) {
    $tile['route'] = $tile['route'] ?: '#';
    return $tile;
}, \App\Services\UserAccessService::dashboardTiles());
?>

<main class="hs-wrap">
    <div class="hs-grid">
        <?php if(empty($tiles)): ?>
            <div class="hs-no-access" style="grid-column: 1 / -1; background:#fff; border-radius:10px; padding:40px 20px; text-align:center; color:#555;">
                <strong>No modules assigned yet.</strong><br>
                Please contact the admin to get access.
            </div>
        <?php endif; ?>
        <?php $__currentLoopData = $tiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($tile['route']); ?>" class="hs-tile"
                <?php if($tile['route'] === '#'): ?> data-todo="<?php echo e($tile['slug']); ?>" <?php endif; ?>>
                <span class="tile-media">
                    <img src="<?php echo e(asset('assets/images/dashboard/' . $tile['image'])); ?>"
                         alt="<?php echo e($tile['label']); ?>"
                         class="dashboard-tile-image">
                </span>
                <span class="tile-label"><?php echo e($tile['label']); ?></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</main>

<style>
    /* Light colour border on every dashboard tile (colours repeat after 8 tiles) */
    .hs-grid .hs-tile {
        --tile-border: #cfe3ff;
        --tile-border-hover: #7fb0f5;
        border: 4px solid var(--tile-border) !important;
        border-radius: 12px;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .hs-grid .hs-tile:nth-of-type(8n+1) { --tile-border: #cfe3ff; --tile-border-hover: #7fb0f5; } /* light blue   */
    .hs-grid .hs-tile:nth-of-type(8n+2) { --tile-border: #d4f1dd; --tile-border-hover: #7cc99a; } /* light green  */
    .hs-grid .hs-tile:nth-of-type(8n+3) { --tile-border: #ffe0c7; --tile-border-hover: #f5a66a; } /* light orange */
    .hs-grid .hs-tile:nth-of-type(8n+4) { --tile-border: #e6dcff; --tile-border-hover: #a58cf0; } /* light purple */
    .hs-grid .hs-tile:nth-of-type(8n+5) { --tile-border: #ffd6de; --tile-border-hover: #f08aa0; } /* light pink   */
    .hs-grid .hs-tile:nth-of-type(8n+6) { --tile-border: #cff1f3; --tile-border-hover: #6cc9cf; } /* light teal   */
    .hs-grid .hs-tile:nth-of-type(8n+7) { --tile-border: #fff1b8; --tile-border-hover: #e6c24a; } /* light yellow */
    .hs-grid .hs-tile:nth-of-type(8n+8) { --tile-border: #e2e8f0; --tile-border-hover: #94a3b8; } /* light grey   */

    .hs-grid .hs-tile:hover {
        border-color: var(--tile-border-hover) !important;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .10);
        transform: translateY(-2px);
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\homeSaaz-complete\homeSaaz\resources\views/dashboard.blade.php ENDPATH**/ ?>
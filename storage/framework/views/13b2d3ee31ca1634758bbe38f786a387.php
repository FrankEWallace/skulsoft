<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo e(config('config.general.meta_description')); ?>">
    <meta name="keywords" content="<?php echo e(config('config.general.meta_keywords')); ?>">
    <meta name="author" content="<?php echo e(config('config.general.meta_author')); ?>">
    <title><?php echo e(config('config.general.app_name', config('app.name', 'ScriptMint'))); ?></title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>" />
    <link rel="icon" href="<?php echo e(config('config.assets.favicon')); ?>" type="image/png">

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>

    <?php echo $__env->make('gateways.assets.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>

<body class="<?php echo e(config('config.layout.display')); ?>">
    <div id="root" class="theme-<?php echo e(config('config.system.color_scheme', 'default')); ?>">
        <router-view></router-view>
    </div>
    <script src="/js/lang"></script>
</body>

</html>
<?php /**PATH /Applications/MAMP/htdocs/InstiKit School v5.0.0 Nulled/schoolms/resources/views/app.blade.php ENDPATH**/ ?>
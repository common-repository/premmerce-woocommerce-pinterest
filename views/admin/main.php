<?php if ( ! defined( 'WPINC' ) ) die; ?>

<div class="wrap">
    <h2 class="nav-tab-wrapper" style="margin-bottom: 10px">
		<?php foreach( $tabs as $tab => $name ): ?>
			<?php $class = ( $tab == $current )? ' nav-tab-active' : ''; ?>
            <a class='nav-tab<?php echo $class ?>'
               href='?page=premmerce-pinterest-page&tab=<?php echo $tab ?>'><?php echo $name ?></a>
		<?php endforeach; ?>
    </h2>

	<?php $file = __DIR__ . "/tabs/{$current}.php" ?>

	<?php if( file_exists( $file ) ): ?>
		<?php $fileManager->includeTemplate( "admin/tabs/{$current}.php", [
            'account'     => $account,
            'fileManager' => $fileManager,
            'queueTable'  => $queueTable,
            'pinnedTable' => $pinnedTable
        ]); ?>
	<?php endif; ?>

</div>
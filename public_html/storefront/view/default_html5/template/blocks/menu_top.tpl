<div class="navbar" id="topnav">
    <div class="navbar-inner">
		<ul id="main_menu_top" class="nav main_menu">
		<?php
			foreach ($storemenu as $i => $menu_item) {
		 	   if ($menu_item['id'] == 'home') {
		    		unset($storemenu[$i]);
		    		break;
		    	}
			}?>
			<?php 
				//NOTE:
				//HTML tree builded in helper/html.php
				//To controll look and style of the menu use CSS in styles.css
			?>
		    <?php echo buildStoreFrontMenuTree( $storemenu ); ?>
		</ul>
    </div>
</div>

<?php

// ------------------------------------------------------------------------------------------------------
// easyads-tab-status
// ------------------------------------------------------------------------------------------------------
//
?>

<div class="easyads-tab easyads-tab-status">
	<table class="easyads-tab easyads-tab-status">
		<tr>
			<td class="easyads-tab-status-td1"><strong>Verbinding</strong>
			<br/>
			</td>
			<td class="easyads-tab-status-td2"><?php if (marktfeed_getConnectionStatus() == 'disconnected') {
			?>
			<div class="easyads-status-off"></div><?php } ?>
			<?php if (marktfeed_getConnectionStatus() == 'waiting_for_callback') {
			?>
			<div class="easyads-status-waiting"></div><?php } ?>
			<?php if (marktfeed_getConnectionStatus() == 'connected') {
			?>
			<div class="easyads-status-on"></div><?php } ?></td>
			<td ><?php if (marktfeed_getConnectionStatus() == 'disconnected') {
			?>
			<p>
				<?php echo __('Not connected','marktfeed'); ?>
				<br>
				<a href="?page=<?php echo MARKTFEED_SLUG; ?>&connect=connect" target="_blank"><?php echo __('Click here to connect','marktfeed'); ?></a>
			</p></td>
		</tr>
	</table>
	<!--  <table class="easyads-tab easyads-tab-status">
	<tr>
		<td class="easyads-tab-status-td1"><strong>Debug</strong><br/></td>
		<td class="easyads-tab-status-td2"></td>
		<td><p><a href="?page=easyads">Ververs deze pagina</a></p></td>
	</tr>
	-->
	<?php } ?>
	<?php if (marktfeed_getConnectionStatus() == 'waiting_for_callback') {
	?>
	<p><meta http-equiv="refresh" content="10; url=?page=<?php echo MARKTFEED_SLUG; ?>" />
		printf( esc_html__( 'Awaiting confirmation of successful connection to your %1$s account.', 'marktfeed' ), MARKTFEED_BRAND_HUMAN );
	</p>
	<p>
		<?php echo __('Do you believe the connection has failed?','marktfeed'); ?> <a href="?page=<?php echo MARKTFEED_SLUG; ?>&connect=connect"  target="_blank">Try connecting again.</a>
	</p>
	</td>
	</tr>
	</table>
	<table class="easyads-tab easyads-tab-status">
		<?php } ?>
		<?php if (marktfeed_getConnectionStatus() == 'connected') {
		?>
		<p>
			<?php echo __('Connected', 'marktfeed'); ?>
			<br>
			<a id="easyads_disconnect" href="#"><?php echo __('Click here to disconnect from '.MARKTFEED_BRAND_HUMAN, 'marktfeed'); ?></a>
		</p>
		<?php } ?>
		</td>
		</tr>
	</table>
</div><!-- .easyads-tab-status -->

<script type="text/javascript">
		window.onload = function() {
var a = document.getElementById("easyads_disconnect");
a.onclick = function() {
easyads_disconnect();
return false;
}
}

function easyads_disconnect() {
// Open a new tab with the disconnect link
var disconnectUrl = "<?php echo marktfeed_getEasyAdsUnlinkUrl(); ?>
	";

	// Call ourselves to disconnect
	window.location.href = "?page=<?php echo MARKTFEED_SLUG; ?>&connect=disconnect";

	// Call Easyads website to disconnect
	window.open(disconnectUrl);
	}

</script>

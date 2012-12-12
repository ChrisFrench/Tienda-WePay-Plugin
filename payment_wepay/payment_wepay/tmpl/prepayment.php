<form action="<?php echo JRoute::_("index.php?option=com_tienda&view=checkout"); ?>" method="post" name="adminForm" enctype="multipart/form-data">

	<input type='hidden' name='cardnum' value='<?php echo @$vars -> cardnum; ?>'>
	<input type='hidden' name='cardexp_month' value='<?php echo @$vars -> cardexp_month; ?>'>
	<input type='hidden' name='cardexp_year' value='<?php echo @$vars -> cardexp_year; ?>'>
	<input type='hidden' name='cardcvv' value='<?php echo @$vars -> cardcvv; ?>'>

	<input type='hidden' name='order_id' value='<?php echo @$vars -> order_id; ?>'>
	<input type='hidden' name='orderpayment_id' value='<?php echo @$vars -> orderpayment_id; ?>'>
	<input type='hidden' name='orderpayment_type' value='<?php echo @$vars -> orderpayment_type; ?>'>
	<input type='hidden' name='task' value='confirmPayment'>
	<input type='hidden' name='paction' value='process'>

	<?php echo JHTML::_('form.token'); ?>

</form>

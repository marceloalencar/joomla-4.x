<?php

/**
 * @package		
 * @subpackage	
 * @copyright	
 * @license		
 */
defined('_JEXEC') or die;

/* marker_class: Class based on the selection of text, none, or icons
 * jicon-text, jicon-none, jicon-icon
 */
?>
<?php if (($this->params->get('address_check') > 0) &&  ($this->item->address || $this->item->suburb  || $this->item->state || $this->item->country || $this->item->postcode)) : ?>
	<div class="contact-address">
	<?php if ($this->params->get('address_check') > 0) : ?>
		<h3>
			Endereço
		</h3>
		<address>
	<?php endif; ?>
	<?php if ($this->item->address && $this->params->get('show_street_address')) : ?>
		<span class="contact-street">
			<?php echo nl2br($this->item->address); ?>
		</span>
	<?php endif; ?>
	<?php if ($this->item->suburb && $this->params->get('show_suburb')) : ?>
		<span class="contact-suburb">
		|	<?php echo $this->item->suburb; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->item->state && $this->params->get('show_state')) : ?>
		<span class="contact-state">
		-	<?php echo $this->item->state; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->item->postcode && $this->params->get('show_postcode')) : ?>
		<span class="contact-postcode">
		|	CEP: <?php echo $this->item->postcode; ?>
		</span>
	<?php endif; ?>
	<?php if ($this->item->country && $this->params->get('show_country')) : ?>
		<span class="contact-country">
		-	<?php echo $this->item->country; ?>
		</span>
	<?php endif; ?>
<?php endif; ?>

<?php if ($this->params->get('address_check') > 0) : ?>
	</address>
	</div>
<?php endif; ?>

<?php if($this->params->get('show_email') || $this->params->get('show_telephone')||$this->params->get('show_fax')||$this->params->get('show_mobile')|| $this->params->get('show_webpage') ) : ?>
	<div class="contact-contactinfo">
<?php endif; ?>
<?php if ($this->item->email_to && $this->params->get('show_email')) : ?>	
		<h3>
			E-mail
		</h3>
	<p>
		<span class="contact-emailto">
			<?php echo $this->item->email_to; ?>
		</span>
	</p>
<?php endif; ?>

<?php if ($this->item->telephone && $this->params->get('show_telephone')) : ?>
		<h3>
			Telefone
		</h3>
	<p>
	
		<span class="contact-telephone">
			<?php echo nl2br($this->item->telephone); ?>
		</span>
	</p>
<?php endif; ?>
<?php if ($this->item->fax && $this->params->get('show_fax')) : ?>
	
		<h3>
			Fax
		</h3>
	<p>
		<span class="contact-fax">
		<?php echo nl2br($this->item->fax); ?>
		</span>
	</p>
<?php endif; ?>
<?php if ($this->item->mobile && $this->params->get('show_mobile')) :?>
		<h3>
			Celular
		</h3>
	<p>
		<span class="contact-mobile">
			<?php echo nl2br($this->item->mobile); ?>
		</span>
	</p>
<?php endif; ?>
<?php if ($this->item->webpage && $this->params->get('show_webpage')) : ?>
	<h3>
		Site
	</h3>
	<p>
		<span class="contact-webpage">
			<a href="<?php echo $this->item->webpage; ?>" target="_blank">
			<?php echo $this->item->webpage; ?></a>
		</span>
	</p>
<?php endif; ?>
<?php if($this->params->get('show_email') || $this->params->get('show_telephone')||$this->params->get('show_fax')||$this->params->get('show_mobile')|| $this->params->get('show_webpage') ) : ?>
	</div>
<?php endif; ?>

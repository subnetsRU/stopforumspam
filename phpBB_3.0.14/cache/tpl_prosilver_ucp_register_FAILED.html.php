<?php 
/*
    Register errors MOD
    by VI:RUS (virus@subnets.ru)
    
    Add this file to forum/cache folder
*/

if (!defined('IN_PHPBB')) exit; $this->_tpl_include('overall_header.html'); 
?>

<div class="panel">
	<div class="inner"><span class="corners-top"><span></span></span>

	<h2><?php echo (isset($this->_rootref['SITENAME'])) ? $this->_rootref['SITENAME'] : ''; ?> - <?php echo ((isset($this->_rootref['L_REGISTRATION'])) ? $this->_rootref['L_REGISTRATION'] : ((isset($user->lang['REGISTRATION'])) ? $user->lang['REGISTRATION'] : '{ REGISTRATION }')); ?></h2>

	<fieldset class="fields2">
	    <p>
		<font color="red"><b>Слишком много ошибок при регистрации... Попробуйте позднее.</b></font>
	    </p>
	    <p>
		<font color="red"><b>Too many register errors... Try again later</b></font>
	    </p>
	</fieldset>
	<span class="corners-bottom"><span></span></span></div>
</div>

<?php $this->_tpl_include('overall_footer.html'); ?>
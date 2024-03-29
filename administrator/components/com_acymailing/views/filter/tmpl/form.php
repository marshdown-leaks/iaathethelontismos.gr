<?php
/**
 * @package	AcyMailing for Joomla!
 * @version	4.9.2
 * @author	acyba.com
 * @copyright	(C) 2009-2015 ACYBA S.A.R.L. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><style type="text/css">
div.plugarea{
	padding:5px;
}
</style>
<?php JHTML::_('behavior.modal'); ?>
<div id="acy_content">
<div id="iframedoc"></div>
<div id="acybase_filters" style="display:none">
	<div id="filters_original">
		<?php echo JHTML::_('select.genericlist', $this->typevaluesFilters, "filter[type][__num__]", 'class="inputbox chzn-done" size="1" onchange="updateFilter(__num__);countresults(__num__);"', 'value', 'text','filtertype__num__');?>
		<span id="countresult___num__"></span>
		<div class="acyfilterarea" id="filterarea___num__"></div>
	</div>
	<?php echo $this->outputFilters; ?>
	<div id="actions_original">
		<?php echo JHTML::_('select.genericlist', $this->typevaluesActions, "action[type][__num__]", 'class="inputbox chzn-done" size="1" onchange="updateAction(__num__);"', 'value', 'text','actiontype__num__');?>
		<div class="acyfilterarea" id="actionarea___num__"></div>
	</div>
	<?php echo $this->outputActions; ?>
</div>
 <?php if(!empty($this->filteredUsers)){ ?>
<fieldset class="adminform" id="filteredUsers">
	<legend>
	<?php
	$usersCount = $this->filteredUsers['countTotal'];
	 echo JText::sprintf('ACY_FILTEREDUSERS',count($this->filteredUsers['users']), $usersCount); ?></legend>
	<div id="acyFilteredUsers" >
		<table class="adminlist table table-striped table-hover" cellpadding="1">
			<thead>
				<tr>
					<th class="title titlenum"><?php echo JText::_( 'ACY_ID' ); ?></th>
					<th class="title titlenum"><?php echo JText::_( 'JOOMEXT_NAME' ); ?></th>
					<th class="title titlenum"><?php echo JText::_( 'JOOMEXT_EMAIL' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$k = 0;
				foreach($this->filteredUsers['users'] as $user){?>
					<tr class="row<?php echo $k; ?>">
						<td align="center" style="text-align:center" ><?php echo $user->subid; ?></td>
						<td align="center" style="text-align:center" ><?php echo $user->name; ?></td>
						<td align="center" style="text-align:center" ><?php echo '<a href="'.acymailing_completeLink('subscriber&task=edit&subid='.$user->subid).'" target="_blank">'.$user->email.'</a>'; ?></td>
					</tr>
				<?php
					$k = 1-$k;
				} ?>
			</tbody>
		</table>
	</div>
</fieldset>
<?php } ?>
<form action="index.php?option=<?php echo ACYMAILING_COMPONENT ?>&amp;ctrl=filter" method="post" name="adminForm"  id="adminForm" autocomplete="off">
	<?php if(JRequest::getCmd('tmpl') == 'component'){
		if(empty($this->subid)){
			acymailing_display(JText::_('PLEASE_SELECT_USERS'),'warning');
			return;
		}
		 ?>

		<input type="hidden" name="subid" value="<?php echo $this->subid; ?>" />
		<input type="hidden" name="tmpl" value="component" />
	<fieldset class="adminform">
		<div class="acyheader icon-48-acyaction" style="float: left;"><?php echo JText::_('ACTIONS'); ?></div>
		<div class="toolbar" id="toolbar" style="float: right;">
			<table><tr>
			<td><a onclick="javascript:if(confirm('<?php echo JText::_('PROCESS_CONFIRMATION',true);?>')){submitbutton('process');} return false;" href="#" ><span class="icon-32-process" title="<?php echo JText::_('PROCESS',true); ?>"></span><?php echo JText::_('PROCESS'); ?></a></td>
			</tr></table>
		</div>
	</fieldset>
	<?php } ?>
	<fieldset class="adminform" id="filterinfo" <?php if(empty($this->filter->filid)) echo 'style="display:none"';?> >
		<legend><?php echo JText::_('ACY_FILTER'); ?></legend>
		<table width="100%" class="paramlist admintable">
			<tr>
					<td class="paramlist_key">
						<label for="title"><?php echo JText::_( 'ACY_TITLE' ); ?></label>
					</td>
					<td class="paramlist_value">
						<input class="inputbox" id="title" type="text" name="data[filter][name]" style="width:250px" value="<?php echo $this->escape(@$this->filter->name); ?>" />
					</td>
					<td class="paramlist_key">
					<label for="published"><?php echo JText::_( 'ACY_PUBLISHED' ); ?></label>
				</td>
				<td class="paramlist_value">
					<?php echo JHTML::_('acyselect.booleanlist', "data[filter][published]" , '',@$this->filter->published); ?>
				</td>
				</tr>
			<tr>
					<td class="paramlist_key" valign="top">
						<label for="description"><?php echo JText::_( 'ACY_DESCRIPTION' ); ?></label>
					</td>
					<td class="paramlist_value" valign="top">
						<textarea id="description" style="width:300px;" rows="5" name="data[filter][description]"><?php echo @$this->filter->description; ?></textarea>
					</td>
					<td width="50%" colspan="2">
						<fieldset class="adminform">
						<legend><?php echo JText::_( 'AUTO_TRIGGER_FILTER' ); ?></legend>
						<?php foreach($this->triggers as $key => $triggerName){
								if(is_object($triggerName)){
									echo $triggerName->name;
									foreach($triggerName->triggers as $subkey => $subTriggerName){ ?>
										<input id="trigger_<?php echo $subkey; ?>" type="checkbox" name="trigger[<?php echo $subkey; ?>]" value="1" <?php if(isset($this->filter->trigger[$subkey])) echo 'checked="checked"'; ?> />
											<label for="trigger_<?php echo $subkey; ?>"><?php echo $subTriggerName; ?></label>
									<?php }
								}else{ ?>
								<input id="trigger_<?php echo $key; ?>" type="checkbox" name="trigger[<?php echo $key; ?>]" value="1" <?php if(isset($this->filter->trigger[$key])) echo 'checked="checked"'; ?> />
									<label for="trigger_<?php echo $key; ?>"><?php echo $triggerName; ?></label><?php echo ($key == 'daycron')?' '.$this->hours.' : '.$this->minutes . ' ' . $this->nextDate:''; ?>
								<?php }?>
								<br />
							<?php } ?>
					</fieldset>
					</td>
				</tr>
		</table>
	</fieldset>
	<?php if(empty($this->subid)) { ?>
	<fieldset class="adminform" >
		<legend><?php echo JText::_( 'ACY_FILTERS' ); ?></legend>
		<div id="allfilters"></div>
		<button class="btn" onclick="addAcyFilter();return false;"><?php echo JText::_('ADD_FILTER'); ?></button>
	</fieldset>
	<?php }?>
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'ACTIONS' ); ?></legend>
		<div id="allactions"></div>
		<button class="btn" onclick="addAction();return false;"><?php echo JText::_('ADD_ACTION'); ?></button>
	</fieldset>

	<div class="clr"></div>

	<input type="hidden" name="option" value="<?php echo ACYMAILING_COMPONENT; ?>" />
	<input type="hidden" name="task" value="process" />
	<input type="hidden" name="ctrl" value="filter" />
	<input type="hidden" name="filid" value="<?php echo @$this->filter->filid; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
<?php if(!empty($this->subid)){ ?>
	<fieldset class="adminform" >
	<legend><?php echo JText::_( 'USERS' ); ?></legend>
		<div id="allfilters" style="display:none"></div>
		<table class="adminlist table table-striped" cellpadding="1">
		<?php
		$k = 0;
		foreach( $this->users as $row){?>
			<tr class="<?php echo "row$k"; ?>">
				<td><?php echo $row->name; ?></td>
				<td><?php echo $row->email; ?></td>
			</tr>
		<?php $k = 1-$k;}

		if(count($this->users) >= 10){?>
		<tr class="<?php echo "row$k"; ?>">
			<td>...</td><td>...</td>
		</tr>
		<?php } ?>
		</table>
	</fieldset>
	<?php }?>
<?php if(!empty($this->filters)){ ?>
<br /><br />
<fieldset class="adminform">
<legend><?php echo JText::_( 'EXISTING_FILTERS' ); ?></legend>
	<table class="adminlist table table-striped table-hover" cellpadding="1">
		<thead>
			<tr>
				<th class="title">
					<?php echo JText::_('ACY_FILTER'); ?>
				</th>
				<th class="title titletoggle">
					<?php echo JText::_('ACY_PUBLISHED'); ?>
				</th>
				<th class="title titletoggle" >
					<?php echo JText::_( 'ACY_DELETE' ); ?>
				</th>
				<th class="title titleid">
					<?php echo JText::_( 'ACY_ID' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
				$k = 0;
				foreach($this->filters as $row){
					$publishedid = 'published_'.$row->filid;
					$id = 'filter_'.$row->filid;
			?>
				<tr class="<?php echo "row$k"; ?>" id="<?php echo $id; ?>">
					<td>
						<?php echo acymailing_tooltip($row->description, $row->name, '', $row->name,acymailing_completeLink('filter&task=edit&filid='.$row->filid)); ?>
					</td>
					<td align="center" style="text-align:center" >
							<span id="<?php echo $publishedid ?>" class="loading"><?php echo $this->toggleClass->toggle($publishedid,(int) $row->published,'filter') ?></span>
					</td>
					<td align="center" style="text-align:center" >
						<?php echo $this->toggleClass->delete($id,$row->filid.'_'.$row->filid,'filter',true); ?>
					</td>
					<td width="1%" align="center">
						<?php echo $row->filid; ?>
					</td>
				</tr>
			<?php
					$k = 1-$k;
				}
			?>
		</tbody>
	</table>
</fieldset>
<?php } ?>
</div>

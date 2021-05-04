<div id="product-flags" class="panel product-tab">
    <input type="hidden" name="submittted_tabs[]" value="ProductFlags">
    <h3>Product Flags</h3>

    <div class="form-group">
		<label class="control-label col-lg-1" for="availableProductFlags">{l s='Flags'}</label>
		<div class="col-lg-9">
			<div class="form-control-static row">
				<div class="col-xs-6">
					<p>{l s='Available Flags'}</p>
					<select id="availableProductFlags" name="availableProductFlags" multiple="multiple">
						{foreach $flag_list as $flag}
							{if !isset($flag.selected) || !$flag.selected}
								<option value="{$flag.id}">{$flag.name}</option>
							{/if}
						{/foreach}
					</select>
					<a href="#" id="addProductFlag" class="btn btn-default btn-block">{l s='Add'} <i class="icon-arrow-right"></i></a>
				</div>
				<div class="col-xs-6">
					<p>{l s='Applied Flags'}</p>
					<select id="selectedProductFlags" name="selectedProductFlags[]" multiple="multiple">
						{foreach $flag_list as $flag}
							{if isset($flag.selected) && $flag.selected}
								<option value="{$flag.id}" selected>{$flag.name}</option>
							{/if}
						{/foreach}
					</select>
					<a href="#" id="removeProductFlag" class="btn btn-default btn-block"><i class="icon-arrow-left"></i> {l s='Remove'}</a>
				</div>
			</div>
		</div>
	</div>

    <div class="panel-footer">
		<a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel'}</a>
		<button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save'}</button>
		<button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and Stay'}</button>
	</div>
</div>
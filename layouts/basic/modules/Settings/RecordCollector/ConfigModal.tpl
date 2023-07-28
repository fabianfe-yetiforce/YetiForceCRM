{*<!-- {[The file is published on the basis of YetiForce Public License 5.0 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-Settings-RecordCollector-ConfigModal -->
	<div class="modal-body pb-0">
		<form class="js-form-validation">
			<div class="row no-gutters">
				<div class="col-sm-18 col-md-12">
					<table class="table table-sm mb-0">
						<tbody class="u-word-break-all small">
						{foreach from=$RECORD_MODEL->getEditFields() item=FIELD_MODEL key=FIELD_NAME name=fields}
							<div class="form-group form-row">
								<label class="col-form-label col-md-4 u-text-small-bold text-left text-md-right">
									{\App\Language::translate($FIELD_MODEL->getFieldLabel(), $QUALIFIED_MODULE)}
									{if $FIELD_MODEL->isMandatory()}<span class="redColor">*</span>{/if}
								</label>
								<div class="col-md-8 fieldValue">
									{include file=\App\Layout::getTemplatePath($FIELD_MODEL->getUITypeModel()->getTemplateName(), $QUALIFIED_MODULE) FIELD_MODEL=$FIELD_MODEL MODULE=$QUALIFIED_MODULE RECORD=true}
								</div>
							</div>
						{/foreach}
						</tbody>
					</table>
				</div>
			</div>
		</form>
	</div>
	<!-- /tpl-Settings-RecordCollector-ConfigModal -->
{/strip}

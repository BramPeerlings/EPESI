<div class="panel panel-{$color}">
	<div class="handle panel-heading clearfix">
		<span class="panel-title">{$caption}</span>

		{if !empty($actions)}
			<div class="pull-left action-buttons">
				{foreach item=action from=$actions}
					{$action}
				{/foreach}
			</div>
		{/if}

		<div class="pull-right action-buttons">
			<div class="pull-right">
				{if isset($href)}
					{$__link.href.open}
					<button class="btn btn-default btn-xs"><i class="fa fa-arrows-alt"></i></button>
					{$__link.href.close}
				{/if}
				{if isset($configure)}
					{$__link.configure.open}
					<button class="btn btn-default btn-xs"><i class="fa fa-cog"></i></button>
					{$__link.configure.close}
				{/if}
				{if isset($remove)}
					{$__link.remove.open}
					<button class="btn btn-default btn-xs"><i class="fa fa-times"></i></button>
					{$__link.remove.close}
				{/if}
			</div>
		</div>

	</div>
	<div class="panel-body">
		{$content}
	</div>
</div>
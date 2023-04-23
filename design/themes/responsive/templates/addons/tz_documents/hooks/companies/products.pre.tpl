{if $company_id}
<p><a title="{__("tz_documents")}" href="{"tz_documents.view?company_id=`$company_id`"|fn_url}" class="ty-vendor-communication__post-write" rel="nofollow">
    <i class="ty-icon-docs"></i>
    {__("vendor_documents")}
</a></p>
{/if}
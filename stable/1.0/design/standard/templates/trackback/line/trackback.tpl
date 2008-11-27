<div class="content-view-line">
    <div class="class-trackback">

    <h3>{attribute_view_gui attribute=$node.data_map.title}</h3>

    <div class="attribute-byline">
        <p class="date">{$node.object.published|l10n(date)}</p>
        <div class="break"></div>
    </div>

    <div class="attribute-blog-name">
    	{attribute_view_gui attribute=$node.data_map.blog_name}
    </div>
    
    <div class="attribute-excerpt">
        {attribute_view_gui attribute=$node.data_map.excerpt}
    </div>
    
    <div class="attribute-url">
    	{attribute_view_gui attribute=$node.data_map.url}
    </div>

    </div>
</div>
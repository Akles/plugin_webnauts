jQuery(document).ready(function ($) {
    function getPosts() {
        var data = {
            action: 'plugin_get_posts',
            post_type: $('#exceptions-post-type').val(),
            search: $('#exceptions-search').val(),
            security: pluginRedirectData.getPostsNonce
        };

        $.post(pluginRedirectData.ajaxUrl, data, function (response) {
            if (response.success) {
                var posts = response.data.posts;
                var tableBody = $('#exceptions-table-body');
                tableBody.empty();

                if (posts.length > 0) {
                    posts.forEach(function (post) {
                        var row = $('<tr></tr>');
                        row.append($('<td></td>').html('<a href="' + post.edit_link + '">' + post.title + '</a>'));
                        row.append($('<td></td>').text(post.date));
                        row.append($('<td></td>').text(post.post_type));
                        row.append($('<td></td>').text(post.categories));

                        var removeButton = $('<button></button>').text('Remove').on('click', function (e) {
                            e.preventDefault()
                            removeException(post.ID, row);
                        });

                        row.append($('<td></td>').append(removeButton));
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append('<tr><td colspan="5">No exceptions found.</td></tr>');
                }
            }
        });
    }

    function removeException(postId, row) {
        var data = {
            action: 'plugin_remove_exception',
            post_id: postId,
            security: pluginRedirectData.removeExceptionNonce
        };

        $.post(pluginRedirectData.ajaxUrl, data, function (response) {
            if (response.success) {
                row.remove();
                alert(response.data.message);
            } else {
                alert(response.data.message);
            }
        });
    }

    $('#exceptions-search').on('input', function () {
        getPosts();
    });

    $('#exceptions-post-type').on('change', function () {
        getPosts();
    });

    getPosts(); // Fetch and display the exception posts when the page loads
});

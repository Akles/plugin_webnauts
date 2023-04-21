alert(123);
document.addEventListener('DOMContentLoaded', function () {
    var addButton = document.getElementById('add-exception');
    var exceptionTable = document.getElementById('exception-table');
    var removeButtons = document.getElementsByClassName('remove-row');
    var postTypeSelector = document.getElementById('post-type-selector');
    var postSelector = document.getElementById('post-selector');
    var confirmSelection = document.getElementById('confirm-selection');
    var exceptionSelector = document.getElementById('exception-selector');

    addButton.addEventListener('click', function (event) {
        event.preventDefault();
        exceptionSelector.style.display = 'block';
    });

    postTypeSelector.addEventListener('change', function () {
        var postType = postTypeSelector.value;
        if (postType) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get_posts_by_post_type&post_type=' + postType
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (posts) {
                    var options = '<option value="">Select Post';
                    posts.forEach(function (post) {
                        options += '<option value="' + post.ID + '">' + post.post_title + '</option>';
                    });
                    postSelector.innerHTML = options;
                    postSelector.style.display = 'inline-block';
                    confirmSelection.style.display = 'inline-block';
                });
        } else {
            postSelector.style.display = 'none';
            confirmSelection.style.display = 'none';
        }
    });

    confirmSelection.addEventListener('click', function (event) {
        event.preventDefault();
        var postId = postSelector.value;
        var postTitle = postSelector.options[postSelector.selectedIndex].text;
        var postType = postTypeSelector.options[postTypeSelector.selectedIndex].text;

        if (postId) {
            var newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="hidden" name="exceptions[]" value="${postId}">
                    ${postType}
                </td>
                <td>
                    ${postTitle}
                </td>
                <td>
                    <button class="button button-secondary remove-row">Remove</button>
                </td>
            `;
            exceptionTable.querySelector('tbody').appendChild(newRow);
            newRow.querySelector('.remove-row').addEventListener('click', removeRow);
            exceptionSelector.style.display = 'none';
        }
    });

    for (var i = 0; i < removeButtons.length; i++) {
        removeButtons[i].addEventListener('click', removeRow);
    }

    function removeRow(event) {
        event.preventDefault();
        var row = event.target.parentNode.parentNode;
        row.parentNode.removeChild(row);
    }
});
(function ($) {
    $(document).ready(function () {
        var popup1 = $('#popup');
        // if (popup1) {
        //     popup1.show();
        // }
        // Проверяем, не авторизован ли пользователь или у него нет подписки
        if (popupParams.userLoggedIn == null ||
            popupParams.userHasSubscription == null ||
            popupParams.userLoggedIn.trim() == '' ||
            popupParams.userHasSubscription.trim() == '' ||
            !popupParams.userLoggedIn ||
            !popupParams.userHasSubscription
        ) {
            // Проверяем, есть ли куки о перенаправлении
            var redirected = getCookie('redirected');
            if (redirected) {
                // Если куки есть, показываем попап
                var popup = $('#popup');
                if (popup) {
                    popup.show();
                }
                // Удаляем куки
                document.cookie = 'redirected=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

                // Закрытие попапа при нажатии на кнопку "назад" на телефоне
                window.addEventListener('popstate', function (event) {
                    if (popup.is(':visible')) {
                        popup.hide();
                    }
                });

                // Закрытие попапа при нажатии клавиши "Escape"
                $(document).on('keyup', function (event) {
                    if (event.key === "Escape" && popup.is(':visible')) {
                        popup.hide();
                    }
                });

                // Закрытие попапа при нажатии на оверлей
                popup.on('click', function (event) {
                    if (event.target == popup.get(0)) {
                        popup.hide();
                    }
                });
            }
        }

        // Инициализируем скрипт для кнопки "Подписаться"
        var subscribeBtn = $('#subscribe-btn');
        if (subscribeBtn) {
            subscribeBtn.click(function (e) {
                e.preventDefault();
                var emailInput = $('input[name="email"]');
                var email = '';
                if (emailInput.length > 0) {
                    email = emailInput.val().trim();
                }
                if (email || popupParams.userLoggedIn) {
                    // Отправляем запрос на создание ордера
                    $.ajax({
                        url: popupParams.ajaxUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'create_order',
                            email: email
                        },
                        success: function (response) {
                            if (response.success) {
                                // Если ордер успешно создан, перенаправляем пользователя на страницу оплаты
                                window.location.href = response.data.payment_url;
                            } else {
                                // Выводим сообщение об ошибке
                                alert(response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            // Выводим сообщение об ошибке
                            alert('Error ' + xhr.status + ': ' + error);
                        }
                    });
                }
            });
        }
    });
})(jQuery);

function getCookie(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length == 2) {
        return parts.pop().split(";").shift();
    }
}

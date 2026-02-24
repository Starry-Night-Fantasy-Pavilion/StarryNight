/**
 * 重置密码页面脚本 - Reset Password Page Script
 */

(function() {
    var sendBtn = document.getElementById('send-reset-code-btn');
    var identifierInput = document.getElementById('identifier');
    var methodInput = document.getElementById('method');
    var msgEl = document.getElementById('reset-code-message');
    var sending = false;
    var countdown = 0;
    var timerId = null;

    function showMsg(text, type) {
        if (!msgEl) return;
        // 处理多行错误信息，将换行符转换为HTML
        var displayText = (text || '').replace(/\n/g, '<br>');
        msgEl.innerHTML = displayText;
        msgEl.classList.remove('error', 'success');
        if (type) {
            msgEl.classList.add(type);
        }
        // 确保错误信息可见
        if (type === 'error' && text) {
            msgEl.style.display = 'block';
            msgEl.style.visibility = 'visible';
        }
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            if (sending || countdown > 0) return;
            
            var identifier = identifierInput ? (identifierInput.value || '').trim() : '';
            var method = methodInput ? (methodInput.value || 'email') : 'email';

            if (!identifier) {
                showMsg('请先填写账号、邮箱或手机号', 'error');
                return;
            }

            // 获取验证码值
            var captchaToken = '';
            var captchaValue = '';
            var captchaInput = document.querySelector('input[name="captcha_value"]');
            var captchaTokenInput = document.querySelector('input[name="captcha_token"]');
            
            if (captchaTokenInput) {
                captchaToken = captchaTokenInput.value || '';
            }
            if (captchaInput) {
                captchaValue = captchaInput.value || '';
            }
            
            // 检查 Cloudflare Turnstile
            if (!captchaValue && document.querySelector('[name="cf-turnstile-response"]')) {
                captchaValue = document.querySelector('[name="cf-turnstile-response"]').value || '';
            }

            if (captchaToken && !captchaValue) {
                showMsg('请完成安全验证', 'error');
                return;
            }

            sending = true;
            var originalText = sendBtn.textContent;
            sendBtn.disabled = true;
            sendBtn.classList.add('sending');
            sendBtn.innerHTML = '<span class="btn-loading-spinner"></span><span class="btn-loading-text">发送中</span>';

            var body = 'identifier=' + encodeURIComponent(identifier) + '&method=' + encodeURIComponent(method);
            if (captchaToken) {
                body += '&captcha_token=' + encodeURIComponent(captchaToken);
            }
            if (captchaValue) {
                body += '&captcha_value=' + encodeURIComponent(captchaValue);
            }

            fetch('/reset-password/send-code', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            }).then(function(res) {
                return res.json().then(function(data) {
                    return { ok: res.ok, status: res.status, data: data };
                }).catch(function() {
                    if (!res.ok) {
                        return { ok: false, status: res.status, data: { success: false, message: '服务器错误 (HTTP ' + res.status + ')，请稍后重试' } };
                    }
                    return { ok: res.ok, status: res.status, data: { success: false, message: '服务器响应格式错误，请稍后重试' } };
                });
            }).then(function(result) {
                var data = result.data;
                if (data && data.success) {
                    showMsg(data.message || '验证码已发送，请注意查收', 'success');

                    // 启动 60 秒倒计时（仅在成功时）
                    countdown = 60;
                    sendBtn.disabled = true;
                    sendBtn.classList.remove('sending');
                    sendBtn.innerHTML = '<span class="btn-countdown-text">' + countdown + ' 秒后可重发</span>';
                    if (timerId) {
                        clearInterval(timerId);
                    }
                    timerId = setInterval(function() {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(timerId);
                            timerId = null;
                            sendBtn.disabled = false;
                            sendBtn.classList.remove('sending');
                            sendBtn.textContent = originalText;
                            showMsg('', null);
                        } else {
                            sendBtn.innerHTML = '<span class="btn-countdown-text">' + countdown + ' 秒后可重发</span>';
                        }
                    }, 1000);
                    
                    // 如果有 token，跳转到第二步
                    if (data.token) {
                        setTimeout(function() {
                            window.location.href = '/reset-password?token=' + encodeURIComponent(data.token);
                        }, 1500);
                    }
                } else {
                    var errorMsg = (data && data.message) ? data.message : '发送失败，请稍后重试';
                    showMsg(errorMsg, 'error');
                    sendBtn.disabled = false;
                    sendBtn.classList.remove('sending');
                    sendBtn.textContent = originalText;
                }
            }).catch(function(err) {
                console.error('发送验证码错误:', err);
                showMsg('网络错误，请检查网络连接后重试', 'error');
                sendBtn.disabled = false;
                sendBtn.classList.remove('sending');
                sendBtn.textContent = originalText;
            }).finally(function() {
                sending = false;
            });
        });
    }
})();

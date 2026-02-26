// 资源分享详情页交互逻辑
// 注意：此文件位于静态资源目录 /static/frontend/web/js/modules/share.js
(function() {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function() {
    var root = document.querySelector('.page-share-detail');
    if (!root) return;

    var shareId = root.getAttribute('data-share-id');
    if (!shareId) return;

    var price = parseInt(root.getAttribute('data-price') || '0', 10) || 0;
    var isPurchased = root.getAttribute('data-purchased') === '1';
    var isFavorited = root.getAttribute('data-favorited') === '1';

    var purchaseBtn = root.querySelector('.js-share-purchase');
    var favoriteBtn = root.querySelector('.js-share-favorite');
    var importBtn = root.querySelector('.js-share-import');
    var ratingStars = root.querySelectorAll('.js-share-rating-star');
    var ratingInput = root.querySelector('input[name="rating"]');
    var ratingSubmit = root.querySelector('.js-share-rate-submit');
    var ratingComment = root.querySelector('textarea[name="rating_comment"]');

    function showMessage(msg) {
      if (window.toastr) {
        window.toastr.info(msg);
      } else {
        window.alert(msg);
      }
    }

    function post(url, data, onSuccess) {
      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams(data)
      }).then(function(res) {
        return res.json();
      }).then(function(json) {
        if (json && json.success) {
          if (onSuccess) onSuccess(json);
        } else {
          showMessage((json && json.message) || '操作失败');
        }
      }).catch(function(err) {
        console.error(err);
        showMessage('请求失败，请稍后重试');
      });
    }

    // 购买按钮
    if (purchaseBtn) {
      if (isPurchased || price <= 0) {
        purchaseBtn.disabled = true;
        purchaseBtn.classList.add('btn-disabled');
      }

      purchaseBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (isPurchased) {
          showMessage('您已购买此资源');
          return;
        }
        if (!window.confirm('确认使用 ' + price + ' 星夜币购买此资源？')) return;

        post('/share/purchase', { share_id: shareId }, function() {
          isPurchased = true;
          root.setAttribute('data-purchased', '1');
          purchaseBtn.disabled = true;
          purchaseBtn.classList.add('btn-disabled');
          showMessage('购买成功');
        });
      });
    }

    // 收藏按钮
    if (favoriteBtn) {
      if (isFavorited) {
        favoriteBtn.classList.add('btn-share-favorited');
        favoriteBtn.textContent = '已收藏';
      }

      favoriteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        post('/share/favorite', { share_id: shareId, action: 'toggle' }, function(json) {
          isFavorited = json.is_favorited === true;
          root.setAttribute('data-favorited', isFavorited ? '1' : '0');
          if (isFavorited) {
            favoriteBtn.classList.add('btn-share-favorited');
            favoriteBtn.textContent = '已收藏';
          } else {
            favoriteBtn.classList.remove('btn-share-favorited');
            favoriteBtn.textContent = '收藏';
          }
        });
      });
    }

    // 导入按钮
    if (importBtn) {
      importBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // 付费资源且未购买时，提示先购买
        if (price > 0 && !isPurchased) {
          showMessage('请先购买该资源，再导入到你的私有库');
          return;
        }

        post('/share/import', { share_id: shareId }, function() {
          showMessage('导入成功，已复制到你的私有库');
        });
      });
    }

    // 星级评分交互
    if (ratingStars && ratingStars.length && ratingInput) {
      ratingStars.forEach(function(star) {
        star.addEventListener('click', function(e) {
          e.preventDefault();
          var val = parseInt(star.getAttribute('data-value') || '0', 10) || 0;
          ratingInput.value = String(val);
          ratingStars.forEach(function(s) {
            var v = parseInt(s.getAttribute('data-value') || '0', 10) || 0;
            if (v <= val) s.classList.remove('inactive');
            else s.classList.add('inactive');
          });
        });
      });
    }

    // 提交评价
    if (ratingSubmit && ratingInput) {
      ratingSubmit.addEventListener('click', function(e) {
        e.preventDefault();
        var val = parseInt(ratingInput.value || '0', 10) || 0;
        if (val < 1 || val > 5) {
          showMessage('请先选择评分');
          return;
        }

        post('/share/rate', {
          share_id: shareId,
          rating: val,
          comment: (ratingComment && ratingComment.value) || ''
        }, function() {
          showMessage('评价成功');
          // 简单处理：刷新页面以更新评分信息
          window.location.reload();
        });
      });
    }
  });
})();
// 未来功能管理页 JS 逻辑

function getAdminPrefix() {
  // 由 layout.php 注入
  return window.ADMIN_PREFIX || 'admin';
}

// 切换功能状态
function toggleFeature(featureKey, enabled) {
  if (confirm('确定要' + (enabled ? '启用' : '禁用') + '此功能吗？')) {
    const adminPrefix = getAdminPrefix();
    fetch('/' + adminPrefix + '/future-features/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'feature_key=' + encodeURIComponent(featureKey) + '&enabled=' + (enabled ? '1' : '0'),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert(data.message || '操作失败');
        }
      })
      .catch((error) => {
        console.error('Error:', error);
        alert('操作失败，请稍后重试');
      });
  }
}

// 测试功能连接
function testFeature(featureKey) {
  const adminPrefix = getAdminPrefix();
  fetch('/' + adminPrefix + '/future-features/test?feature=' + encodeURIComponent(featureKey))
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const result = data.data;
        let message = '功能测试结果：\n\n';
        message += '功能：' + result.feature + '\n';
        message += '状态：' + (result.enabled ? '已启用' : '已禁用') + '\n';
        message += '连接测试：' + result.connection_test + '\n';
        message += '测试时间：' + result.timestamp;
        alert(message);
      } else {
        alert(data.message || '测试失败');
      }
    })
    .catch((error) => {
      console.error('Error:', error);
      alert('测试失败，请稍后重试');
    });
}

// 查看功能统计
function viewFeatureStats(featureKey) {
  const adminPrefix = getAdminPrefix();
  fetch('/' + adminPrefix + '/future-features/stats')
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const stats = data.data[featureKey.replace('feature_', '')];
        if (stats) {
          alert('统计数据：\n' + JSON.stringify(stats, null, 2));
        } else {
          alert('暂无统计数据');
        }
      } else {
        alert('加载统计数据失败：' + (data.message || '未知错误'));
      }
    })
    .catch((error) => {
      console.error('Error:', error);
      alert('加载统计数据失败，请稍后重试');
    });
}

// 启用所有功能
function enableAllFeatures() {
  if (confirm('确定要启用所有功能吗？')) {
    const features = [
      'feature_ai_agent_market',
      'feature_collaboration',
      'feature_copyright_protection',
      'feature_recommendation_system',
      'feature_creation_contests',
      'feature_education_training',
    ];
    const adminPrefix = getAdminPrefix();
    fetch('/' + adminPrefix + '/future-features/batch-update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        features: features.reduce((obj, key) => {
          obj[key] = true;
          return obj;
        }, {}),
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert(data.message || '操作失败');
        }
      })
      .catch((error) => {
        console.error('Error:', error);
        alert('操作失败，请稍后重试');
      });
  }
}

// 禁用所有功能
function disableAllFeatures() {
  if (confirm('确定要禁用所有功能吗？这将影响用户体验。')) {
    const features = [
      'feature_ai_agent_market',
      'feature_collaboration',
      'feature_copyright_protection',
      'feature_recommendation_system',
      'feature_creation_contests',
      'feature_education_training',
    ];
    const adminPrefix = getAdminPrefix();
    fetch('/' + adminPrefix + '/future-features/batch-update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        features: features.reduce((obj, key) => {
          obj[key] = false;
          return obj;
        }, {}),
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert(data.message || '操作失败');
        }
      })
      .catch((error) => {
        console.error('Error:', error);
        alert('操作失败，请稍后重试');
      });
  }
}

// 刷新功能统计
function refreshFeatureStats() {
  location.reload();
}


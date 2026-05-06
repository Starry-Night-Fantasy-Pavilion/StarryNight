<template>
  <div class="system-integration-config page-container">
    <div class="page-header">
      <h1>系统配置</h1>
      <p class="page-header__hint">
        参数写入 MySQL <code>system_config</code>。<strong>找回密码验证码</strong>：优先短信（配置完整时），否则邮件（SMTP 且用户有邮箱）；发邮件须先在「邮件模板」上传对应 HTML。易支付见「支付配置」。<strong>第三方登录</strong>按渠道分组默认收起，按需展开并<strong>单独启用</strong>即可，不必全开。
      </p>
    </div>

    <el-card>
      <el-tabs v-model="activeTab" class="config-tabs" @tab-change="onTabChange">
        <el-tab-pane label="邮箱配置" name="email">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            发信参数可与 Spring <code>spring.mail.*</code> 对齐；「允许邮箱注册」与前台注册是否展示邮箱字段一致。
          </el-alert>
          <div v-loading="mailLoading" class="tab-body">
            <el-form label-width="160px" style="max-width: 560px">
              <el-form-item label="启用邮件发送">
                <el-switch v-model="mailForm.enabled" />
              </el-form-item>
              <el-form-item label="SMTP 主机">
                <el-input v-model="mailForm.host" placeholder="smtp.example.com" />
              </el-form-item>
              <el-form-item label="SMTP 端口">
                <el-input-number v-model="mailForm.port" :min="1" :max="65535" style="width: 100%" />
              </el-form-item>
              <el-form-item label="用户名">
                <el-input v-model="mailForm.username" autocomplete="off" />
              </el-form-item>
              <el-form-item label="密码">
                <el-input v-model="mailForm.password" type="password" show-password autocomplete="off" />
              </el-form-item>
              <el-form-item label="发件人地址">
                <el-input v-model="mailForm.from" placeholder="可空，与发信账号一致；与控制台「发信域名」一致" />
              </el-form-item>
              <el-form-item label="发件者显示名">
                <el-input v-model="mailForm.fromPersonal" placeholder="可选，如：星夜阁（收件箱显示名称与发件地址）" />
              </el-form-item>
              <el-form-item label="启用 STARTTLS">
                <el-switch v-model="mailForm.startTls" />
              </el-form-item>
              <el-form-item label="SSL 直连（465）">
                <el-switch v-model="mailForm.ssl" />
              </el-form-item>
              <el-divider content-position="left">注册</el-divider>
              <el-form-item label="允许邮箱注册">
                <el-switch v-model="mailForm.registerEmailEnabled" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="mailSaving" @click="saveMail">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <el-tab-pane label="短信配置" name="sms">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            服务商选<strong>阿里云</strong>时，请看下方<strong>橙色必读</strong>：控制台模板变量名必须为 <code>code</code>。<strong>腾讯云</strong>需填 SdkAppId、地域；模板 ID 为控制台数字 ID。
          </el-alert>
          <div v-loading="smsLoading" class="tab-body">
            <el-form label-width="160px" style="max-width: 560px">
              <el-form-item label="启用短信">
                <el-switch v-model="smsForm.enabled" />
              </el-form-item>
              <el-form-item label="服务商">
                <el-select v-model="smsForm.provider" placeholder="选择服务商" style="width: 100%">
                  <el-option label="阿里云" value="aliyun" />
                  <el-option label="腾讯云" value="tencent" />
                </el-select>
              </el-form-item>
              <el-form-item label="AccessKey ID">
                <el-input v-model="smsForm.accessKeyId" autocomplete="off" />
              </el-form-item>
              <el-form-item label="AccessKey Secret">
                <el-input v-model="smsForm.accessKeySecret" type="password" show-password autocomplete="off" />
              </el-form-item>
              <el-form-item label="短信签名">
                <el-input v-model="smsForm.signName" placeholder="已通过审核的签名" />
              </el-form-item>
              <el-form-item label="验证码模板">
                <el-input v-model="smsForm.templateVerification" placeholder="阿里云填 SMS_xxxx；腾讯云填数字模板 ID" />
              </el-form-item>
              <el-alert
                v-if="smsForm.provider === 'aliyun'"
                type="warning"
                :closable="false"
                show-icon
                class="tab-alert sms-aliyun-code-hint"
              >
                <p class="sms-hint-title">阿里云控制台必填（否则会发送失败）</p>
                <p>
                  登录<strong>阿里云短信服务</strong> → 国内消息 → 模板管理，新增/编辑验证码模板时：<strong>模板变量名必须命名为
                  <code>code</code></strong>（全小写，与后端 JSON <code>{"code":"验证码"}</code> 一致）。
                </p>
                <p class="sms-hint-sub">
                  正文示例：<code>您的验证码为${code}</code>（若控制台占位符写法不同，仍以<strong>变量名为 code</strong>为准）。
                </p>
              </el-alert>
              <template v-if="smsForm.provider === 'tencent'">
                <el-form-item label="腾讯云 SdkAppId">
                  <el-input v-model="smsForm.tencentSdkAppId" placeholder="短信控制台应用 ID" />
                </el-form-item>
                <el-form-item label="腾讯云地域">
                  <el-input v-model="smsForm.tencentRegion" placeholder="ap-guangzhou" />
                </el-form-item>
              </template>
              <el-divider content-position="left">注册</el-divider>
              <el-form-item label="允许手机号注册">
                <el-switch v-model="smsForm.registerPhoneEnabled" />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="smsSaving" @click="saveSms">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <el-tab-pane label="第三方登录" name="oauth">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            按需展开渠道并填写。各渠道内「站点回调根地址」为同一项（改任意一处即可），须与开放平台里登记的回调根一致；本站 OAuth 回调路径为
            <code>/api/auth/oauth/{渠道}/callback</code>。知我云聚合回调：<code>{{ zevostCallbackHint }}</code>。若网页与接口不在同一域名，请在部署时配置「浏览器访问后端的根地址」，或由技术人员在系统配置库中维护（本页不展示该项）。
          </el-alert>
          <div v-loading="oauthLoading" class="tab-body">
            <el-form label-width="180px" style="max-width: 680px" class="oauth-form">
              <el-divider content-position="left">各渠道配置</el-divider>
              <el-collapse v-model="oauthCollapseOpen" class="oauth-section-collapse">
                <el-collapse-item name="wechat">
                  <template #title>
                    <span class="oauth-collapse-title">
                      <img :src="OAUTH_LOGO.wechat" alt="" width="20" height="20" decoding="async" referrerpolicy="no-referrer" />
                      微信登录
                      <a
                        class="config-key-link"
                        href="https://open.weixin.qq.com/"
                        target="_blank"
                        rel="noopener"
                        @click.stop
                      >申请密钥</a>
                    </span>
                  </template>
                  <el-form-item label="站点回调根地址">
                    <el-input
                      v-model="oauthForm.publicBaseUrl"
                      placeholder="如 https://你的域名 无尾斜杠；各渠道 OAuth 回调根与此相同，改一处即可"
                    />
                  </el-form-item>
                  <el-form-item label="启用">
                    <el-switch v-model="oauthForm.wechatEnabled" />
                  </el-form-item>
                  <el-form-item label="微信 AppID">
                    <el-input v-model="oauthForm.wechatClientId" placeholder="开放平台网站应用 AppID" />
                  </el-form-item>
                  <el-form-item label="微信 AppSecret">
                    <el-input v-model="oauthForm.wechatClientSecret" type="password" show-password placeholder="开放平台 AppSecret" />
                  </el-form-item>
                  <el-form-item label="扫码授权接口地址">
                    <el-input
                      v-model="oauthForm.wechatOpenPlatformBaseUrl"
                      placeholder="留空为官方 https://open.weixin.qq.com（网站应用扫码 /connect/qrconnect）"
                    />
                  </el-form-item>
                  <el-form-item label="换票与用户信息接口地址">
                    <el-input
                      v-model="oauthForm.wechatSnsApiBaseUrl"
                      placeholder="留空为官方 https://api.weixin.qq.com（/sns/oauth2/access_token 等）"
                    />
                  </el-form-item>
                </el-collapse-item>
                <el-collapse-item name="qq">
                  <template #title>
                    <span class="oauth-collapse-title">
                      <img :src="OAUTH_LOGO.qq" alt="" width="20" height="20" decoding="async" referrerpolicy="no-referrer" />
                      QQ 登录
                      <a
                        class="config-key-link"
                        href="https://connect.qq.com/manage.html#/"
                        target="_blank"
                        rel="noopener"
                        @click.stop
                      >申请密钥</a>
                    </span>
                  </template>
                  <el-form-item label="站点回调根地址">
                    <el-input
                      v-model="oauthForm.publicBaseUrl"
                      placeholder="如 https://你的域名 无尾斜杠；与各渠道共用，改一处即可"
                    />
                  </el-form-item>
                  <el-form-item label="启用">
                    <el-switch v-model="oauthForm.qqEnabled" />
                  </el-form-item>
                  <el-form-item label="QQ 互联 AppID">
                    <el-input v-model="oauthForm.qqClientId" placeholder="QQ 互联应用的 AppID" />
                  </el-form-item>
                  <el-form-item label="QQ 互联 AppKey">
                    <el-input v-model="oauthForm.qqClientSecret" type="password" show-password placeholder="作为 OAuth client_secret 使用" />
                  </el-form-item>
                  <el-form-item label="开放平台接口地址">
                    <el-input
                      v-model="oauthForm.qqOpenApiBaseUrl"
                      placeholder="留空为官方 https://graph.qq.com（授权、换票、用户信息等）"
                    />
                  </el-form-item>
                </el-collapse-item>
                <el-collapse-item name="github">
                  <template #title>
                    <span class="oauth-collapse-title">
                      <img
                        :src="OAUTH_LOGO.github"
                        alt=""
                        width="20"
                        height="20"
                        decoding="async"
                        referrerpolicy="no-referrer"
                        class="oauth-collapse-title__github-mark"
                      />
                      GitHub 登录
                      <a
                        class="config-key-link"
                        href="https://github.com/settings/developers"
                        target="_blank"
                        rel="noopener"
                        @click.stop
                      >申请密钥</a>
                    </span>
                  </template>
                  <el-form-item label="站点回调根地址">
                    <el-input
                      v-model="oauthForm.publicBaseUrl"
                      placeholder="如 https://你的域名 无尾斜杠；与各渠道共用，改一处即可"
                    />
                  </el-form-item>
                  <el-form-item label="启用">
                    <el-switch v-model="oauthForm.githubEnabled" />
                  </el-form-item>
                  <el-form-item label="GitHub Client ID">
                    <el-input v-model="oauthForm.githubClientId" placeholder="OAuth App 的 Client ID" />
                  </el-form-item>
                  <el-form-item label="GitHub Client Secret">
                    <el-input v-model="oauthForm.githubClientSecret" type="password" show-password placeholder="Client secrets" />
                  </el-form-item>
                  <el-form-item label="OAuth 网页接口地址">
                    <el-input
                      v-model="oauthForm.githubOauthWebBaseUrl"
                      placeholder="留空为官方 https://github.com（授权与换票路径）"
                    />
                  </el-form-item>
                  <el-form-item label="REST API 接口地址">
                    <el-input
                      v-model="oauthForm.githubRestApiBaseUrl"
                      placeholder="留空为官方 https://api.github.com（拉用户资料）"
                    />
                  </el-form-item>
                </el-collapse-item>
                <el-collapse-item name="google">
                  <template #title>
                    <span class="oauth-collapse-title">
                      <img :src="OAUTH_LOGO.google" alt="" width="20" height="20" decoding="async" referrerpolicy="no-referrer" />
                      谷歌登录
                      <a
                        class="config-key-link"
                        href="https://console.cloud.google.com/apis/credentials"
                        target="_blank"
                        rel="noopener"
                        @click.stop
                      >申请密钥</a>
                    </span>
                  </template>
                  <el-form-item label="站点回调根地址">
                    <el-input
                      v-model="oauthForm.publicBaseUrl"
                      placeholder="如 https://你的域名 无尾斜杠；与各渠道共用，改一处即可"
                    />
                  </el-form-item>
                  <el-form-item label="启用">
                    <el-switch v-model="oauthForm.googleEnabled" />
                  </el-form-item>
                  <el-form-item label="Google Client ID">
                    <el-input v-model="oauthForm.googleClientId" placeholder="OAuth 2.0 Web 客户端 ID" />
                  </el-form-item>
                  <el-form-item label="Google Client Secret">
                    <el-input v-model="oauthForm.googleClientSecret" type="password" show-password placeholder="客户端密钥" />
                  </el-form-item>
                  <el-form-item label="授权页接口地址">
                    <el-input
                      v-model="oauthForm.googleAccountsBaseUrl"
                      placeholder="留空为官方 https://accounts.google.com"
                    />
                  </el-form-item>
                  <el-form-item label="令牌接口地址">
                    <el-input
                      v-model="oauthForm.googleTokenBaseUrl"
                      placeholder="留空为官方 https://oauth2.googleapis.com"
                    />
                  </el-form-item>
                  <el-form-item label="用户信息接口地址">
                    <el-input
                      v-model="oauthForm.googleUserinfoBaseUrl"
                      placeholder="留空为官方 https://openidconnect.googleapis.com"
                    />
                  </el-form-item>
                </el-collapse-item>
                <el-collapse-item name="linuxdo">
                  <template #title>
                    <span class="oauth-collapse-title">
                      <img
                        class="oauth-collapse-title__linuxdo"
                        :src="OAUTH_LOGO.linuxdo"
                        alt=""
                        width="20"
                        height="20"
                        decoding="async"
                        referrerpolicy="no-referrer"
                      />
                      LINUX DO 登录
                      <a
                        class="config-key-link"
                        href="https://connect.linux.do/"
                        target="_blank"
                        rel="noopener"
                        @click.stop
                      >申请密钥</a>
                    </span>
                  </template>
                  <el-form-item label="站点回调根地址">
                    <el-input
                      v-model="oauthForm.publicBaseUrl"
                      placeholder="如 https://你的域名 无尾斜杠；与各渠道共用，改一处即可"
                    />
                  </el-form-item>
                  <el-form-item label="启用 LINUX DO 登录">
                    <el-switch v-model="oauthForm.linuxdoEnabled" />
                  </el-form-item>
                  <el-form-item label="Client ID">
                    <el-input v-model="oauthForm.linuxdoClientId" type="password" show-password placeholder="在 Connect 应用页查看" />
                  </el-form-item>
                  <el-form-item label="Client Secret">
                    <el-input v-model="oauthForm.linuxdoClientSecret" type="password" show-password placeholder="在 Connect 应用页查看" />
                  </el-form-item>
                  <el-form-item label="Connect 接口地址">
                    <el-input
                      v-model="oauthForm.linuxdoPlatformBaseUrl"
                      placeholder="留空为官方 https://connect.linux.do"
                    />
                  </el-form-item>
                </el-collapse-item>
              </el-collapse>

              <el-divider content-position="left">
                <span class="divider-with-key-link">
                  知我云聚合登录
                  <a
                    class="config-key-link"
                    href="https://u.zevost.com/doc.php"
                    target="_blank"
                    rel="noopener"
                    @click.stop
                  >申请密钥</a>
                </span>
              </el-divider>
              <el-alert type="warning" :closable="false" show-icon class="tab-alert oauth-zevost-alert">
                协议见
                <a href="https://u.zevost.com/doc.php" target="_blank" rel="noopener">知我云开发文档</a>
                。请在聚合后台将回调登记为 <code>{{ zevostCallbackHint }}</code>（须与各渠道内「站点回调根地址」一致）。开启总开关并填写 AppID/AppKey 后，下列分项可单独启用；登录页仅展示已启用的分项。
              </el-alert>
              <el-form-item label="站点回调根地址">
                <el-input
                  v-model="oauthForm.publicBaseUrl"
                  placeholder="如 https://你的域名 无尾斜杠；与各渠道共用，改一处即可"
                />
              </el-form-item>
              <el-form-item label="启用聚合">
                <el-switch v-model="oauthForm.zevostEnabled" />
              </el-form-item>
              <el-form-item label="聚合接口地址">
                <el-input
                  v-model="oauthForm.zevostPlatformBaseUrl"
                  placeholder="留空为官方 https://u.zevost.com（文档 connect.php）"
                />
              </el-form-item>
              <el-form-item label="知我云 AppID">
                <el-input v-model="oauthForm.zevostAppId" placeholder="聚合后台 appid" />
              </el-form-item>
              <el-form-item label="知我云 AppKey">
                <el-input v-model="oauthForm.zevostAppKey" type="password" show-password placeholder="聚合后台 appkey" />
              </el-form-item>
              <p class="oauth-zevost-types-caption">
                聚合内登录方式（与文档 type 一致，可单独开关）
                <a class="config-key-link" href="https://u.zevost.com/doc.php" target="_blank" rel="noopener">申请密钥</a>
              </p>
              <el-row :gutter="12" class="oauth-zevost-type-grid">
                <el-col v-for="zt in ZEVOST_TYPE_ORDER" :key="zt" :xs="24" :sm="12">
                  <el-form-item :label="ZEVOST_TYPE_LABELS[zt] ?? zt">
                    <el-switch v-model="oauthForm.zevostTypes[zt]" />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item>
                <el-button type="primary" :loading="oauthSaving" @click="saveOauth">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <el-tab-pane label="实名认证配置" name="realname">
          <el-alert type="info" :closable="false" show-icon class="tab-alert">
            开启后用户需在<strong>个人中心</strong>登记真实姓名与 18 位身份证；未完成核验前无法导出作品内容。核验方式：<strong>支付宝</strong>对接开放平台人脸；
            <strong>喵雨欣开发平台</strong>为可配置 HTTP 调用（
            <a href="https://www.ovooa.cc/apidata?id=4" target="_blank" rel="noopener">调用说明</a> /
            <a href="https://www.ovooa.cc/apidata?id=5" target="_blank" rel="noopener">回调说明</a>
            ）。用户同步跳转与 OAuth 回跳均使用 <code>auth.oauth.public-base-url</code>（站点公网根，无尾斜杠）。
          </el-alert>
          <div v-loading="realnameLoading" class="tab-body">
            <el-form label-width="180px" class="realname-form-wide">
              <el-form-item label="启用实名认证">
                <el-switch v-model="realnameForm.enabled" />
              </el-form-item>
              <el-form-item label="核验方式">
                <el-select v-model="realnameForm.verifyProvider" style="width: 280px">
                  <el-option label="支付宝" value="alipay" />
                  <el-option label="喵雨欣开发平台" value="ovooa" />
                </el-select>
              </el-form-item>

              <template v-if="realnameForm.verifyProvider === 'alipay'">
                <el-divider content-position="left">支付宝开放平台</el-divider>
                <el-form-item label="AppID">
                  <el-input v-model="realnameForm.alipayAppId" placeholder="开放平台应用 APPID" clearable />
                </el-form-item>
                <el-form-item label="应用私钥 PEM">
                  <el-input
                    v-model="realnameForm.alipayPrivateKey"
                    type="textarea"
                    :rows="4"
                    placeholder="RSA2 PKCS8，可多行 PEM"
                  />
                </el-form-item>
                <el-form-item label="支付宝公钥 PEM">
                  <el-input
                    v-model="realnameForm.alipayPublicKey"
                    type="textarea"
                    :rows="3"
                    placeholder="用于验签异步通知"
                  />
                </el-form-item>
                <el-form-item label="网关">
                  <el-input v-model="realnameForm.alipayGateway" placeholder="https://openapi.alipay.com/gateway.do" />
                </el-form-item>
                <el-form-item label="人脸 biz_code">
                  <el-input v-model="realnameForm.alipayFaceBizCode" placeholder="如 FACE_CERTIFY" />
                </el-form-item>
              </template>

              <template v-if="realnameForm.verifyProvider === 'ovooa'">
                <el-divider content-position="left">喵雨欣开发平台</el-divider>
                <el-form-item label="调用接口 URL">
                  <el-input v-model="realnameForm.ovooaInvokeUrl" placeholder="控制台提供的完整调用地址" clearable />
                </el-form-item>
                <el-form-item label="API Token">
                  <el-input
                    v-model="realnameForm.ovooaApiToken"
                    type="password"
                    show-password
                    placeholder="写入 Authorization: Bearer …"
                    clearable
                  />
                </el-form-item>
                <el-form-item label="调用 JSON 模板">
                  <el-input
                    v-model="realnameForm.ovooaInvokeJsonTemplate"
                    type="textarea"
                    :rows="5"
                    placeholder='占位：{realName} {idCard} {notifyUrl} {userId}'
                  />
                </el-form-item>
                <el-form-item label="回调密钥">
                  <el-input v-model="realnameForm.ovooaCallbackSecret" type="password" show-password clearable />
                </el-form-item>
                <el-form-item label="回调密钥请求头名">
                  <el-input v-model="realnameForm.ovooaCallbackSecretHeader" placeholder="如 X-Realname-Secret，空则不校验" />
                </el-form-item>
              </template>

              <el-divider content-position="left">认证费</el-divider>
              <el-form-item label="收取实名认证费">
                <el-switch v-model="realnameForm.feeEnabled" />
              </el-form-item>
              <p class="realname-fee-hint">
                开启后，用户须先在个人中心通过<strong>易支付</strong>缴纳下方金额（现金），支付成功后再可点击「前往核验」；请在本页「支付配置」中填写
                <code>payment.epay.*</code>。
              </p>
              <el-form-item label="认证费金额（元）">
                <el-input-number
                  v-model="realnameForm.feeAmountYuan"
                  :min="0"
                  :max="99999"
                  :precision="2"
                  :step="0.5"
                  style="width: 220px"
                />
              </el-form-item>

              <el-form-item>
                <el-button type="primary" :loading="realnameSaving" @click="saveRealname">保存</el-button>
              </el-form-item>
            </el-form>
          </div>
        </el-tab-pane>

        <el-tab-pane label="支付配置" name="payment" lazy>
          <div class="tab-body">
            <PaymentConfig embedded />
          </div>
        </el-tab-pane>

        <el-tab-pane label="邮件模板" name="mailTemplate" lazy>
          <div class="tab-body">
            <MailTemplateConfig embedded />
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import type { SystemConfigItem } from '@/types/api'
import { listSystemConfigs, updateSystemConfig } from '@/api/systemConfig'
import PaymentConfig from '@/views/admin/PaymentConfig.vue'
import MailTemplateConfig from '@/views/admin/MailTemplateConfig.vue'

const route = useRoute()
const router = useRouter()

type ConfigTabName = 'email' | 'sms' | 'oauth' | 'realname' | 'payment' | 'mailTemplate'
const VALID_TAB_NAMES: ConfigTabName[] = ['email', 'sms', 'oauth', 'realname', 'payment', 'mailTemplate']

const activeTab = ref<ConfigTabName>('email')

watch(
  () => route.query.tab,
  (tab) => {
    const s = typeof tab === 'string' ? tab : undefined
    if (s && VALID_TAB_NAMES.includes(s as ConfigTabName)) {
      activeTab.value = s as ConfigTabName
    }
  },
  { immediate: true }
)

/** 仅「第三方登录」Tab：分组默认全部收起，按需展开配置 */
const oauthCollapseOpen = ref<string[]>([])

const MAIL_KEYS = [
  'mail.enabled',
  'spring.mail.host',
  'spring.mail.port',
  'spring.mail.username',
  'spring.mail.password',
  'mail.from',
  'mail.from.personal',
  'mail.smtp.starttls',
  'mail.smtp.ssl'
] as const

const SMS_KEYS = [
  'sms.enabled',
  'sms.provider',
  'sms.access-key-id',
  'sms.access-key-secret',
  'sms.sign-name',
  'sms.template.verification',
  'sms.tencent.sdk-app-id',
  'sms.tencent.region'
] as const

/** 运营页展示用品牌图标（GitHub 勿用 file://，浏览器无法加载本地路径） */
const OAUTH_LOGO = {
  wechat: 'https://images.icon-icons.com/2108/PNG/512/wechat_icon_130789.png',
  qq: 'https://images.icon-icons.com/1753/PNG/512/iconfinder-social-media-applications-10qq-4102582_113820.png',
  /** GitHub Mark（SVG）；折叠标题内需配浅色底，否则暗色主题下与背景同色不可见 */
  github: 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.svg',
  google: 'https://images.icon-icons.com/673/PNG/512/Google_icon-icons.com_60497.png',
  /** GitHub 组织头像（图形标）；论坛横幅图多为「左图右字」，裁圆后易变成字母标 */
  linuxdo: 'https://avatars.githubusercontent.com/u/160804563?s=128&v=4'
} as const

const OAUTH_KEYS = [
  'auth.oauth.wechat.enabled',
  'auth.oauth.wechat.client-id',
  'auth.oauth.wechat.client-secret',
  'auth.oauth.qq.enabled',
  'auth.oauth.qq.client-id',
  'auth.oauth.qq.client-secret',
  'auth.oauth.github.enabled',
  'auth.oauth.github.client-id',
  'auth.oauth.github.client-secret',
  'auth.oauth.google.enabled',
  'auth.oauth.google.client-id',
  'auth.oauth.google.client-secret',
  'auth.oauth.linuxdo.enabled',
  'auth.oauth.linuxdo.client-id',
  'auth.oauth.linuxdo.client-secret',
  'auth.oauth.public-base-url'
] as const

const ZEVOST_TYPE_ORDER = [
  'qq',
  'wx',
  'alipay',
  'sina',
  'baidu',
  'douyin',
  'huawei',
  'xiaomi',
  'google',
  'microsoft',
  'twitter',
  'dingtalk',
  'gitee',
  'github'
] as const

const ZEVOST_TYPE_LABELS: Record<string, string> = {
  qq: 'QQ',
  wx: '微信',
  alipay: '支付宝',
  sina: '微博',
  baidu: '百度',
  douyin: '抖音',
  huawei: '华为',
  xiaomi: '小米',
  google: 'Google',
  microsoft: '微软',
  twitter: 'Twitter',
  dingtalk: '钉钉',
  gitee: 'Gitee',
  github: 'GitHub'
}

const ZEVOST_KEYS = [
  'auth.oauth.zevost.enabled',
  'auth.oauth.zevost.platform-base-url',
  'auth.oauth.zevost.app-id',
  'auth.oauth.zevost.app-key',
  ...ZEVOST_TYPE_ORDER.map((t) => `auth.oauth.zevost.type.${t}.enabled`)
] as const

/** Optional override of third-party OAuth/OpenAPI origins (same paths as official docs). */
const OAUTH_PLATFORM_URL_KEYS = [
  'auth.oauth.linuxdo.platform-base-url',
  'auth.oauth.github.oauth-web-base-url',
  'auth.oauth.github.rest-api-base-url',
  'auth.oauth.google.accounts-base-url',
  'auth.oauth.google.token-base-url',
  'auth.oauth.google.userinfo-base-url',
  'auth.oauth.wechat.open-platform-base-url',
  'auth.oauth.wechat.sns-api-base-url',
  'auth.oauth.qq.open-api-base-url'
] as const

const ALL_OAUTH_KEYS = [...OAUTH_KEYS, ...OAUTH_PLATFORM_URL_KEYS, ...ZEVOST_KEYS] as readonly string[]

function emptyZevostTypes(): Record<string, boolean> {
  const o: Record<string, boolean> = {}
  for (const t of ZEVOST_TYPE_ORDER) o[t] = false
  return o
}

const zevostCallbackHint = computed(() => {
  const base = oauthForm.publicBaseUrl.trim().replace(/\/+$/, '')
  if (!base) return '{站点公网根}/api/auth/oauth/zevost/callback'
  return `${base}/api/auth/oauth/zevost/callback`
})

const mailLoading = ref(false)
const mailSaving = ref(false)
const mailRows = ref<Record<string, SystemConfigItem>>({})
const mailForm = reactive({
  enabled: false,
  host: '',
  port: 587,
  username: '',
  password: '',
  from: '',
  fromPersonal: '',
  startTls: true,
  ssl: false,
  registerEmailEnabled: true
})
let mailAuthEmailRow: SystemConfigItem | undefined

const smsLoading = ref(false)
const smsSaving = ref(false)
const smsRows = ref<Record<string, SystemConfigItem>>({})
const smsForm = reactive({
  enabled: false,
  provider: 'aliyun',
  accessKeyId: '',
  accessKeySecret: '',
  signName: '',
  templateVerification: '',
  tencentSdkAppId: '',
  tencentRegion: 'ap-guangzhou',
  registerPhoneEnabled: true
})
let smsAuthPhoneRow: SystemConfigItem | undefined

const oauthLoading = ref(false)
const oauthSaving = ref(false)
const oauthRows = ref<Record<string, SystemConfigItem>>({})
const oauthForm = reactive({
  wechatEnabled: false,
  wechatClientId: '',
  wechatClientSecret: '',
  wechatOpenPlatformBaseUrl: '',
  wechatSnsApiBaseUrl: '',
  qqEnabled: false,
  qqClientId: '',
  qqClientSecret: '',
  qqOpenApiBaseUrl: '',
  githubEnabled: false,
  githubClientId: '',
  githubClientSecret: '',
  githubOauthWebBaseUrl: '',
  githubRestApiBaseUrl: '',
  googleEnabled: false,
  googleClientId: '',
  googleClientSecret: '',
  googleAccountsBaseUrl: '',
  googleTokenBaseUrl: '',
  googleUserinfoBaseUrl: '',
  linuxdoEnabled: false,
  linuxdoClientId: '',
  linuxdoClientSecret: '',
  linuxdoPlatformBaseUrl: '',
  publicBaseUrl: '',
  zevostEnabled: false,
  zevostPlatformBaseUrl: '',
  zevostAppId: '',
  zevostAppKey: '',
  zevostTypes: emptyZevostTypes()
})

const REALNAME_KEYS = [
  'auth.realname.enabled',
  'auth.realname.verify_provider',
  'auth.realname.fee.enabled',
  'auth.realname.fee.amount-yuan',
  'auth.realname.alipay.app-id',
  'auth.realname.alipay.private-key',
  'auth.realname.alipay.alipay-public-key',
  'auth.realname.alipay.gateway',
  'auth.realname.alipay.face-biz-code',
  'auth.realname.ovooa.invoke-url',
  'auth.realname.ovooa.api-token',
  'auth.realname.ovooa.invoke-json-template',
  'auth.realname.ovooa.callback-secret',
  'auth.realname.ovooa.callback-secret-header'
] as const

type RealnameProviderTab = 'alipay' | 'ovooa'

const realnameLoading = ref(false)
const realnameSaving = ref(false)
const realnameRows = ref<Record<string, SystemConfigItem>>({})
const realnameForm = reactive({
  enabled: false,
  verifyProvider: 'alipay' as RealnameProviderTab,
  alipayAppId: '',
  alipayPrivateKey: '',
  alipayPublicKey: '',
  alipayGateway: '',
  alipayFaceBizCode: '',
  ovooaInvokeUrl: '',
  ovooaApiToken: '',
  ovooaInvokeJsonTemplate: '',
  ovooaCallbackSecret: '',
  ovooaCallbackSecretHeader: '',
  feeEnabled: false,
  feeAmountYuan: 0
})

function indexByKey(list: SystemConfigItem[], keys: string[]) {
  const m: Record<string, SystemConfigItem> = {}
  for (const k of keys) {
    const row = list.find((c) => c.configKey === k)
    if (row) m[k] = row
  }
  return m
}

function onTabChange(name: string | number) {
  const tab = String(name) as ConfigTabName
  if (VALID_TAB_NAMES.includes(tab) && route.query.tab !== tab) {
    router.replace({ query: { ...route.query, tab } })
  }
  if (name === 'email') void loadMail()
  if (name === 'sms') void loadSms()
  if (name === 'oauth') void loadOauth()
  if (name === 'realname') void loadRealname()
}

async function loadMail() {
  mailLoading.value = true
  try {
    const [mailList, authList] = await Promise.all([listSystemConfigs('mail'), listSystemConfigs('auth')])
    mailRows.value = indexByKey(mailList, [...MAIL_KEYS])
    const em = authList.find((c) => c.configKey === 'auth.register.email.enabled')
    mailAuthEmailRow = em
    mailForm.enabled = (mailRows.value['mail.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    mailForm.host = mailRows.value['spring.mail.host']?.configValue ?? ''
    mailForm.port = Number(mailRows.value['spring.mail.port']?.configValue ?? 587)
    mailForm.username = mailRows.value['spring.mail.username']?.configValue ?? ''
    mailForm.password = mailRows.value['spring.mail.password']?.configValue ?? ''
    mailForm.from = mailRows.value['mail.from']?.configValue ?? ''
    mailForm.fromPersonal = mailRows.value['mail.from.personal']?.configValue ?? ''
    mailForm.startTls = (mailRows.value['mail.smtp.starttls']?.configValue ?? 'true').toLowerCase() === 'true'
    mailForm.ssl = (mailRows.value['mail.smtp.ssl']?.configValue ?? 'false').toLowerCase() === 'true'
    mailForm.registerEmailEnabled = (em?.configValue ?? 'true').toLowerCase() === 'true'
  } finally {
    mailLoading.value = false
  }
}

async function saveMail() {
  const rows = { ...mailRows.value }
  const patch: { key: string; val: string; type?: 'boolean' }[] = [
    { key: 'mail.enabled', val: mailForm.enabled ? 'true' : 'false', type: 'boolean' },
    { key: 'spring.mail.host', val: mailForm.host },
    { key: 'spring.mail.port', val: String(mailForm.port) },
    { key: 'spring.mail.username', val: mailForm.username },
    { key: 'spring.mail.password', val: mailForm.password },
    { key: 'mail.from', val: mailForm.from },
    { key: 'mail.from.personal', val: mailForm.fromPersonal },
    { key: 'mail.smtp.starttls', val: mailForm.startTls ? 'true' : 'false', type: 'boolean' },
    { key: 'mail.smtp.ssl', val: mailForm.ssl ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.register.email.enabled', val: mailForm.registerEmailEnabled ? 'true' : 'false', type: 'boolean' }
  ]
  for (const { key } of patch) {
    if (key === 'auth.register.email.enabled') {
      if (!mailAuthEmailRow?.id) {
        ElMessage.warning('缺少「允许邮箱注册」配置项，请执行最新 seed / patch')
        return
      }
    } else if (!rows[key]?.id) {
      ElMessage.warning(`缺少配置 ${key}，请执行最新 seed / patch_system_config_mail_sms_oauth.sql`)
      return
    }
  }
  mailSaving.value = true
  try {
    for (const { key, val, type } of patch) {
      if (key === 'auth.register.email.enabled') {
        await updateSystemConfig({ ...mailAuthEmailRow!, configValue: val, configType: type ?? 'boolean' })
      } else {
        const row = rows[key]!
        await updateSystemConfig({ ...row, configValue: val, ...(type ? { configType: type } : {}) })
      }
    }
    ElMessage.success('邮箱相关配置已保存')
    await loadMail()
  } finally {
    mailSaving.value = false
  }
}

async function loadSms() {
  smsLoading.value = true
  try {
    const [smsList, authList] = await Promise.all([listSystemConfigs('sms'), listSystemConfigs('auth')])
    smsRows.value = indexByKey(smsList, [...SMS_KEYS])
    const ph = authList.find((c) => c.configKey === 'auth.register.phone.enabled')
    smsAuthPhoneRow = ph
    smsForm.enabled = (smsRows.value['sms.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    smsForm.provider = smsRows.value['sms.provider']?.configValue || 'aliyun'
    smsForm.accessKeyId = smsRows.value['sms.access-key-id']?.configValue ?? ''
    smsForm.accessKeySecret = smsRows.value['sms.access-key-secret']?.configValue ?? ''
    smsForm.signName = smsRows.value['sms.sign-name']?.configValue ?? ''
    smsForm.templateVerification = smsRows.value['sms.template.verification']?.configValue ?? ''
    smsForm.tencentSdkAppId = smsRows.value['sms.tencent.sdk-app-id']?.configValue ?? ''
    smsForm.tencentRegion = smsRows.value['sms.tencent.region']?.configValue ?? 'ap-guangzhou'
    smsForm.registerPhoneEnabled = (ph?.configValue ?? 'true').toLowerCase() === 'true'
  } finally {
    smsLoading.value = false
  }
}

async function saveSms() {
  const rows = { ...smsRows.value }
  const patch: { key: string; val: string; type?: 'boolean' }[] = [
    { key: 'sms.enabled', val: smsForm.enabled ? 'true' : 'false', type: 'boolean' },
    { key: 'sms.provider', val: smsForm.provider },
    { key: 'sms.access-key-id', val: smsForm.accessKeyId },
    { key: 'sms.access-key-secret', val: smsForm.accessKeySecret },
    { key: 'sms.sign-name', val: smsForm.signName },
    { key: 'sms.template.verification', val: smsForm.templateVerification },
    { key: 'sms.tencent.sdk-app-id', val: smsForm.tencentSdkAppId },
    { key: 'sms.tencent.region', val: smsForm.tencentRegion || 'ap-guangzhou' },
    { key: 'auth.register.phone.enabled', val: smsForm.registerPhoneEnabled ? 'true' : 'false', type: 'boolean' }
  ]
  for (const { key } of patch) {
    if (key === 'auth.register.phone.enabled') {
      if (!smsAuthPhoneRow?.id) {
        ElMessage.warning('缺少「允许手机号注册」配置项，请执行最新 seed / patch')
        return
      }
    } else if (!rows[key]?.id) {
      ElMessage.warning(`缺少配置 ${key}，请执行最新 seed / patch_system_config_mail_sms_oauth.sql`)
      return
    }
  }
  smsSaving.value = true
  try {
    for (const { key, val, type } of patch) {
      if (key === 'auth.register.phone.enabled') {
        await updateSystemConfig({ ...smsAuthPhoneRow!, configValue: val, configType: type ?? 'boolean' })
      } else {
        await updateSystemConfig({ ...rows[key]!, configValue: val, ...(type ? { configType: type } : {}) })
      }
    }
    ElMessage.success('短信相关配置已保存')
    await loadSms()
  } finally {
    smsSaving.value = false
  }
}

async function loadOauth() {
  oauthLoading.value = true
  try {
    const list = await listSystemConfigs('oauth')
    oauthRows.value = indexByKey(list, [...ALL_OAUTH_KEYS])
    oauthForm.wechatEnabled =
      (oauthRows.value['auth.oauth.wechat.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    oauthForm.qqEnabled = (oauthRows.value['auth.oauth.qq.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    oauthForm.githubEnabled =
      (oauthRows.value['auth.oauth.github.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    oauthForm.googleEnabled =
      (oauthRows.value['auth.oauth.google.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    oauthForm.wechatClientId = oauthRows.value['auth.oauth.wechat.client-id']?.configValue ?? ''
    oauthForm.wechatClientSecret = oauthRows.value['auth.oauth.wechat.client-secret']?.configValue ?? ''
    oauthForm.qqClientId = oauthRows.value['auth.oauth.qq.client-id']?.configValue ?? ''
    oauthForm.qqClientSecret = oauthRows.value['auth.oauth.qq.client-secret']?.configValue ?? ''
    oauthForm.githubClientId = oauthRows.value['auth.oauth.github.client-id']?.configValue ?? ''
    oauthForm.githubClientSecret = oauthRows.value['auth.oauth.github.client-secret']?.configValue ?? ''
    oauthForm.googleClientId = oauthRows.value['auth.oauth.google.client-id']?.configValue ?? ''
    oauthForm.googleClientSecret = oauthRows.value['auth.oauth.google.client-secret']?.configValue ?? ''
    oauthForm.linuxdoEnabled =
      (oauthRows.value['auth.oauth.linuxdo.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    oauthForm.linuxdoClientId = oauthRows.value['auth.oauth.linuxdo.client-id']?.configValue ?? ''
    oauthForm.linuxdoClientSecret = oauthRows.value['auth.oauth.linuxdo.client-secret']?.configValue ?? ''
    oauthForm.linuxdoPlatformBaseUrl = oauthRows.value['auth.oauth.linuxdo.platform-base-url']?.configValue ?? ''
    oauthForm.publicBaseUrl = oauthRows.value['auth.oauth.public-base-url']?.configValue ?? ''
    oauthForm.wechatOpenPlatformBaseUrl =
      oauthRows.value['auth.oauth.wechat.open-platform-base-url']?.configValue ?? ''
    oauthForm.wechatSnsApiBaseUrl = oauthRows.value['auth.oauth.wechat.sns-api-base-url']?.configValue ?? ''
    oauthForm.qqOpenApiBaseUrl = oauthRows.value['auth.oauth.qq.open-api-base-url']?.configValue ?? ''
    oauthForm.githubOauthWebBaseUrl = oauthRows.value['auth.oauth.github.oauth-web-base-url']?.configValue ?? ''
    oauthForm.githubRestApiBaseUrl = oauthRows.value['auth.oauth.github.rest-api-base-url']?.configValue ?? ''
    oauthForm.googleAccountsBaseUrl = oauthRows.value['auth.oauth.google.accounts-base-url']?.configValue ?? ''
    oauthForm.googleTokenBaseUrl = oauthRows.value['auth.oauth.google.token-base-url']?.configValue ?? ''
    oauthForm.googleUserinfoBaseUrl = oauthRows.value['auth.oauth.google.userinfo-base-url']?.configValue ?? ''
    oauthForm.zevostEnabled =
      (oauthRows.value['auth.oauth.zevost.enabled']?.configValue ?? 'false').toLowerCase() === 'true'
    oauthForm.zevostPlatformBaseUrl = oauthRows.value['auth.oauth.zevost.platform-base-url']?.configValue ?? ''
    oauthForm.zevostAppId = oauthRows.value['auth.oauth.zevost.app-id']?.configValue ?? ''
    oauthForm.zevostAppKey = oauthRows.value['auth.oauth.zevost.app-key']?.configValue ?? ''
    for (const t of ZEVOST_TYPE_ORDER) {
      const key = `auth.oauth.zevost.type.${t}.enabled` as const
      oauthForm.zevostTypes[t] =
        (oauthRows.value[key]?.configValue ?? 'false').toLowerCase() === 'true'
    }
  } finally {
    oauthLoading.value = false
  }
}

async function saveOauth() {
  const rows = oauthRows.value
  const patch: { key: string; val: string; type?: 'boolean' | 'string' }[] = [
    { key: 'auth.oauth.wechat.enabled', val: oauthForm.wechatEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.oauth.qq.enabled', val: oauthForm.qqEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.oauth.github.enabled', val: oauthForm.githubEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.oauth.google.enabled', val: oauthForm.googleEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.oauth.wechat.client-id', val: oauthForm.wechatClientId, type: 'string' },
    { key: 'auth.oauth.wechat.client-secret', val: oauthForm.wechatClientSecret, type: 'string' },
    { key: 'auth.oauth.qq.client-id', val: oauthForm.qqClientId, type: 'string' },
    { key: 'auth.oauth.qq.client-secret', val: oauthForm.qqClientSecret, type: 'string' },
    { key: 'auth.oauth.github.client-id', val: oauthForm.githubClientId, type: 'string' },
    { key: 'auth.oauth.github.client-secret', val: oauthForm.githubClientSecret, type: 'string' },
    { key: 'auth.oauth.google.client-id', val: oauthForm.googleClientId, type: 'string' },
    { key: 'auth.oauth.google.client-secret', val: oauthForm.googleClientSecret, type: 'string' },
    { key: 'auth.oauth.linuxdo.enabled', val: oauthForm.linuxdoEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.oauth.linuxdo.client-id', val: oauthForm.linuxdoClientId, type: 'string' },
    { key: 'auth.oauth.linuxdo.client-secret', val: oauthForm.linuxdoClientSecret, type: 'string' },
    { key: 'auth.oauth.linuxdo.platform-base-url', val: oauthForm.linuxdoPlatformBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.public-base-url', val: oauthForm.publicBaseUrl, type: 'string' },
    { key: 'auth.oauth.wechat.open-platform-base-url', val: oauthForm.wechatOpenPlatformBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.wechat.sns-api-base-url', val: oauthForm.wechatSnsApiBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.qq.open-api-base-url', val: oauthForm.qqOpenApiBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.github.oauth-web-base-url', val: oauthForm.githubOauthWebBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.github.rest-api-base-url', val: oauthForm.githubRestApiBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.google.accounts-base-url', val: oauthForm.googleAccountsBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.google.token-base-url', val: oauthForm.googleTokenBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.google.userinfo-base-url', val: oauthForm.googleUserinfoBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.zevost.enabled', val: oauthForm.zevostEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.oauth.zevost.platform-base-url', val: oauthForm.zevostPlatformBaseUrl.trim(), type: 'string' },
    { key: 'auth.oauth.zevost.app-id', val: oauthForm.zevostAppId, type: 'string' },
    { key: 'auth.oauth.zevost.app-key', val: oauthForm.zevostAppKey, type: 'string' },
    ...ZEVOST_TYPE_ORDER.map((t) => ({
      key: `auth.oauth.zevost.type.${t}.enabled`,
      val: oauthForm.zevostTypes[t] ? 'true' : 'false',
      type: 'boolean' as const
    }))
  ]
  for (const { key } of patch) {
    if (!rows[key]?.id) {
      ElMessage.warning(
        `缺少配置 ${key}，请执行最新 seed 或 patch：patch_auth_oauth_platform_endpoint_urls.sql、patch_system_config_mail_sms_oauth.sql、patch_auth_oauth_zevost.sql 等`
      )
      return
    }
  }
  oauthSaving.value = true
  try {
    for (const { key, val, type } of patch) {
      await updateSystemConfig({
        ...rows[key]!,
        configValue: val,
        configType: type ?? 'string'
      })
    }
    ElMessage.success('第三方登录配置已保存')
    await loadOauth()
  } catch (e: any) {
    ElMessage.error(e?.message || '保存失败，请查看后端日志')
  } finally {
    oauthSaving.value = false
  }
}

async function loadRealname() {
  realnameLoading.value = true
  try {
    const list = await listSystemConfigs('auth')
    realnameRows.value = indexByKey(list, [...REALNAME_KEYS])
    const R = realnameRows.value
    const gv = (k: string, d = '') => R[k]?.configValue ?? d
    realnameForm.enabled = gv('auth.realname.enabled', 'false').toLowerCase() === 'true'
    const pv = gv('auth.realname.verify_provider', 'alipay').trim().toLowerCase()
    if (pv === 'ovooa' || pv === 'miaoyuxin') realnameForm.verifyProvider = 'ovooa'
    else realnameForm.verifyProvider = 'alipay'
    realnameForm.alipayAppId = gv('auth.realname.alipay.app-id')
    realnameForm.alipayPrivateKey = gv('auth.realname.alipay.private-key')
    realnameForm.alipayPublicKey = gv('auth.realname.alipay.alipay-public-key')
    realnameForm.alipayGateway = gv('auth.realname.alipay.gateway')
    realnameForm.alipayFaceBizCode = gv('auth.realname.alipay.face-biz-code')
    realnameForm.ovooaInvokeUrl = gv('auth.realname.ovooa.invoke-url')
    realnameForm.ovooaApiToken = gv('auth.realname.ovooa.api-token')
    realnameForm.ovooaInvokeJsonTemplate = gv('auth.realname.ovooa.invoke-json-template')
    realnameForm.ovooaCallbackSecret = gv('auth.realname.ovooa.callback-secret')
    realnameForm.ovooaCallbackSecretHeader = gv('auth.realname.ovooa.callback-secret-header')
    realnameForm.feeEnabled = gv('auth.realname.fee.enabled', 'false').toLowerCase() === 'true'
    realnameForm.feeAmountYuan = Number(gv('auth.realname.fee.amount-yuan', '0')) || 0
  } finally {
    realnameLoading.value = false
  }
}

async function saveRealname() {
  const rows = realnameRows.value
  for (const key of REALNAME_KEYS) {
    if (!rows[key]?.id) {
      ElMessage.warning(`缺少配置 ${key}，请在库中执行 patch_auth_realname_provider.sql`)
      return
    }
  }
  const patch: { key: (typeof REALNAME_KEYS)[number]; val: string; type?: string }[] = [
    { key: 'auth.realname.enabled', val: realnameForm.enabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.realname.verify_provider', val: realnameForm.verifyProvider, type: 'string' },
    { key: 'auth.realname.fee.enabled', val: realnameForm.feeEnabled ? 'true' : 'false', type: 'boolean' },
    { key: 'auth.realname.fee.amount-yuan', val: String(realnameForm.feeAmountYuan ?? 0), type: 'number' },
    { key: 'auth.realname.alipay.app-id', val: realnameForm.alipayAppId.trim(), type: 'string' },
    { key: 'auth.realname.alipay.private-key', val: realnameForm.alipayPrivateKey, type: 'string' },
    { key: 'auth.realname.alipay.alipay-public-key', val: realnameForm.alipayPublicKey, type: 'string' },
    { key: 'auth.realname.alipay.gateway', val: realnameForm.alipayGateway.trim(), type: 'string' },
    { key: 'auth.realname.alipay.face-biz-code', val: realnameForm.alipayFaceBizCode.trim(), type: 'string' },
    { key: 'auth.realname.ovooa.invoke-url', val: realnameForm.ovooaInvokeUrl.trim(), type: 'string' },
    { key: 'auth.realname.ovooa.api-token', val: realnameForm.ovooaApiToken, type: 'string' },
    { key: 'auth.realname.ovooa.invoke-json-template', val: realnameForm.ovooaInvokeJsonTemplate, type: 'string' },
    { key: 'auth.realname.ovooa.callback-secret', val: realnameForm.ovooaCallbackSecret, type: 'string' },
    { key: 'auth.realname.ovooa.callback-secret-header', val: realnameForm.ovooaCallbackSecretHeader.trim(), type: 'string' }
  ]
  realnameSaving.value = true
  try {
    for (const { key, val, type } of patch) {
      await updateSystemConfig({
        ...rows[key]!,
        configValue: val,
        configType: type ?? 'string'
      })
    }
    ElMessage.success('实名认证配置已保存')
    await loadRealname()
  } finally {
    realnameSaving.value = false
  }
}

void Promise.all([loadMail(), loadSms(), loadOauth(), loadRealname()])
</script>

<style scoped lang="scss">
.system-integration-config {
  .page-header__hint {
    margin: $space-sm 0 0;
    font-size: $font-size-sm;
    color: $text-muted;
    max-width: 720px;
    line-height: 1.55;

    code {
      font-size: $font-size-xs;
      padding: 2px 6px;
      border-radius: 4px;
      background: $bg-elevated;
    }
  }
}

.config-tabs {
  :deep(.el-tabs__content) {
    padding-top: $space-md;
  }
}

.tab-alert {
  margin-bottom: $space-md;
}

.tab-body {
  min-height: 120px;
}

.realname-form-wide {
  max-width: 720px;
}

.divider-with-key-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.config-key-link {
  margin-left: 2px;
  font-size: 12px;
  font-weight: 400;
  color: var(--el-color-primary);
  text-decoration: none;
  white-space: nowrap;

  &:hover {
    text-decoration: underline;
  }
}

.divider-with-key-link .config-key-link {
  margin-left: 0;
}

.sms-aliyun-code-hint {
  margin-top: $space-sm;

  .sms-hint-title {
    margin: 0 0 $space-xs;
    font-weight: 600;
  }

  p {
    margin: 0 0 $space-xs;
    line-height: 1.55;
    font-size: $font-size-sm;
    color: $text-secondary;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .sms-hint-sub {
    font-size: $font-size-xs;
    color: $text-muted;
  }

  code {
    font-size: $font-size-xs;
    padding: 1px 6px;
    border-radius: 4px;
    background: $bg-elevated;
  }
}

.oauth-form {
  .oauth-form-item-label {
    display: inline-flex;
    align-items: center;
    gap: $space-sm;
    font-weight: 500;

    img {
      width: 22px;
      height: 22px;
      object-fit: contain;
      flex-shrink: 0;
    }
  }
}

.oauth-section-collapse {
  border: none;
  margin-bottom: $space-md;
  --el-collapse-border-color: transparent;

  /**
   * EP 默认 header line-height = 48px，槽内只有图片时极易与箭头/其它「图标+文案」行纵向错位；
   * 标题区改为普通行高 + flex 居中即可对齐。
   */
  :deep(.el-collapse-item__header) {
    font-weight: 600;
    line-height: normal;
    background: transparent;
    border-bottom: 1px solid $border-color;
  }

  :deep(.el-collapse-item__title) {
    display: flex;
    align-items: center;
    line-height: normal;
  }

  :deep(.el-collapse-item__wrap) {
    background: transparent;
    border-bottom: none;
  }

  :deep(.el-collapse-item__content) {
    padding-bottom: $space-sm;
  }
}

.oauth-collapse-title {
  display: inline-flex;
  align-items: center;
  gap: $space-sm;
  flex-wrap: wrap;
  font-weight: 600;
  line-height: 1;

  img {
    width: 20px;
    height: 20px;
    object-fit: contain;
    flex-shrink: 0;
    display: block;
  }

  /** 与 Login 页 GitHub 按钮一致：深色 Mark 需浅色底，否则暗色主题下不可见 */
  .oauth-collapse-title__github-mark {
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.92);
    padding: 2px;
    box-sizing: border-box;
  }

  .oauth-collapse-title__linuxdo {
    border-radius: 50%;
    object-fit: cover;
    object-position: center;
  }
}
</style>

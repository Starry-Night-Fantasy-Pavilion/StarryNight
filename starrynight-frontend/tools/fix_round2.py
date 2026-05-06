# -*- coding: utf-8 -*-
"""Round 2: invalid UTF-8 (e3 80 ?) + phrase repairs after bad auto-fix."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "src"

# Longest-first phrase replacements (UTF-8 strings)
_RAW = [
        ("两次不一臀)", "两次不一致')"),
        ("两次不一臀)", "两次不一致')"),
        ("请输入密砀", "请输入密码"),
        ("请确认密砀", "请确认密码"),
        ("新密砀 prop", "新密码\" prop"),
        ("验证砀>", "验证码\">"),
        ("label=\"新密砀", "label=\"新密码"),
        ("请输入正确邮简", "请输入正确邮箱"),
        ("请输入当前密砀", "请输入当前密码"),
        ("至少6佀", "至少6位"),
        ("6-32佀", "6-32位"),
        ("2-20佀", "2-20位"),
        ("密码 (6-32佀", "密码 (6-32位"),
        ("{0:'普通用戀,1:", "{0:'普通用户',1:"),
        ("||'普通用戀 })", "||'普通用户' })"),
        ("|| '普通用戀 })", "|| '普通用户' })"),
        ("已退出登彀", "已退出登录"),
        ("退出登彀", "退出登录"),
        ("前往用户端登彀", "前往用户端登录"),
        ("请使用新密码登彀", "请使用新密码登录"),
        ("知识庀, icon", "知识库', icon"),
        ("角色庀, icon", "角色库', icon"),
        ("素材庀, icon", "素材库', icon"),
        ("工具简, icon", "工具箱', icon"),
        ("{ 0: '普通用戀, 1:", "{ 0: '普通用户', 1:"),
        ("return map[level] || '普通用戀\n", "return map[level] || '普通用户')\n"),
        ("return map[level] || '普通用戀", "return map[level] || '普通用户'"),
        ("ElMessage.success('已退出登彀)", "ElMessage.success('已退出登录')"),
        ("知识庀/el-dropdown", "知识库</el-dropdown"),
        ("素材庀/el-dropdown", "素材库</el-dropdown"),
        ("角色庀/el-dropdown", "角色库</el-dropdown"),
        ("知识庀/h2>", "知识库</h2>"),
        ("角色庀/h2>", "角色库</h2>"),
        ("素材庀/h1>", "素材库</h1>"),
        ("知识庀/span>", "知识库</span>"),
        ("角色庀/span>", "角色库</span>"),
        ("素材庀/span>", "素材库</span>"),
        ("匹配庀", "匹配度"),
        ("订单记彀", "订单记录"),
        ("待支什", "待支付"),
        ("已支什", "已支付"),
        ("已完戀", "已完成"),
        ("已取涀", "已取消"),
        ("已退欀", "已退款"),
        ("写作一,", "写作中',"),
        ("写作一,", "写作中',"),
        ("已发帀", "已发布"),
        ("已发帀,", "已发布',"),
        ("开始创佀", "开始创作"),
        ("创建第一部作哀", "创建第一部作品"),
        ("精彩的故亀", "精彩的故事"),
        ("每一个环芀", "每一个环节"),
        ("创作工具铀", "创作工具箱"),
        ("知识库辅劀", "知识库辅助"),
        ("精准创佀", "精准创作"),
        ("保持风格一臀", "保持风格一致"),
        ("角色库管琀", "角色库管理"),
        ("提示词模杀", "提示词模板"),
        ("工具简, desc", "工具箱', desc"),
        ("{ value: '50一'", "{ value: '50万+'"),
        ("服务可用玀", "服务可用性"),
        ("创作耀", "创作者"),
        ("风格优匀", "风格优化"),
        ("多卷多竀", "多卷多线"),
        ("星夜阅/span>", "星夜</span>"),
        ("简洁精炀 value", "简洁精炼\" value"),
        ("concise: '简洁精炀,", "concise: '简洁精炼',"),
        ("一键使甀", "一键使用"),
        ("点击右上角新廀", "点击右上角新建"),
        ("新廀 />", "新建\" />"),
        ("世界觀,", "世界观',"),
        ("自定乀", "自定义"),
        ("自定乀>", "自定义\">"),
        ("自定乀/", "自定义</"),
        ("自定乀 value", "自定义\" value"),
        ("自定乀}", "自定义'}"),
        ("参考资斀", "参考资料"),
        ("参考资斀 value", "参考资料\" value"),
        ("参考资斀,", "参考资料',"),
        ("处理一 value", "处理中\" value"),
        ("处理一,", "处理中',"),
        ("暂无知识庀", "暂无知识库"),
        ("编辑知识庀 width", "编辑知识库\" width"),
        ("当前创作上下斀>", "当前创作上下文\">"),
        ("已更斀)", "已更新')"),
        ("已更斀);", "已更新');"),
        ("密码已修攀", "密码已修改"),
        ("已修攀:", "已修改':"),
        ("已创廀)", "已创建')"),
        ("创作炀,", "创作点',"),
        ("星夜帀,", "星夜币',"),
        ("已过最", "已过期"),
        ("生效一,", "生效中',"),
        ("已过最,", "已过期',"),
        ("已取涀};", "已取消'};"),
        ("建讀200", "建议 200"),
        ("仅创意模式可调强庀 disabled", "仅创意模式可调强度\" disabled"),
        ("正文编辑噀", "正文编辑器"),
        ("一致性检柀", "一致性检查"),
        ("一致性检柀 width", "一致性检查\" width"),
        ("问颀)", "问题')"),
        ("请重识)", "请重试')"),
        ("保存一..", "保存中.."),
        ("加载一..", "加载中.."),
        ("已生戀", "已生成"),
        ("衔接检查完戀", "衔接检查完成"),
        ("未设定身什", "未设定身份"),
        ("总字一", "总字数"),
        ("卷管一", "卷管理"),
        ("时间一", "时间线"),
        ("已过一/span>", "已过期</span>"),
        ("炀/span>", "点</span>"),
        ("炀/div>", "点</div>"),
        ("知识库数一", "知识库数量"),
        ("开通成一", "开通成功"),
        ("立即开一", "立即开通"),
        ("创意素杀", "创意素材"),
        ("生成简什", "生成简介"),
        ("作品简什", "作品简介"),
        ("label=\"简什", "label=\"简介"),
        ("保存到素材庀", "保存到素材库"),
        ("已保存到素材庀", "已保存到素材库"),
        ("简什 prop", "简介\" prop"),
        ("角色庀/h2>", "角色库</h2>"),
        ("知识庀/h2>", "知识库</h2>"),
        ("顀/span>", "个</span>"),
        ("佀/span>", "个</span>"),
        ("一/span>", "个</span>"),
        ("coreEvent: intent.value.coreEvent || '正文一致性检柀,", "coreEvent: intent.value.coreEvent || '正文一致性检查',"),
        ("lblMap:Record<string,string>={ daily_free_quota:'每日额度',outline_per_day:'大纲次数',content_per_day:'正文次数',knowledge_library_limit:'知识库数一", "lblMap:Record<string,string>={ daily_free_quota:'每日额度',outline_per_day:'大纲次数',content_per_day:'正文次数',knowledge_library_limit:'知识库数量"),
        ("ElMessage.success('开通成一", "ElMessage.success('开通成功'"),
        ("创作工具铀/h2>", "创作工具箱</h2>"),
        ("故亀/p>", "故事</p>"),
        ("环芀/p>", "环节</p>"),
        ("支持多卷多竀, color", "支持多卷多线', color"),
        ("积累优质提示词模杀, color", "积累优质提示词模板', color"),
        ("星夜阅/span>", "星夜</span>"),
    ]
# dedupe by old string, longest first
_seen = {}
for a, b in _RAW:
    _seen[a] = b
PHRASES = sorted(_seen.items(), key=lambda x: -len(x[0]))


def fix_bytes_all(data: bytes) -> bytes:
    data = data.replace(b"\xe3\x80?", b"\xe3\x80\x82")  # 。 before broken tag
    data = data.replace(b"\xef\xbc?", b"\xef\xbc\xbf")  # ？
    data = data.replace(b"\xe2\x9a?", b"\xe2\x9a\xa1")  # ⚡
    data = data.replace(b"\xe2\x80?", b"\xe2\x80\xa6")  # …
    return data


def main() -> None:
    exts = (".vue", ".ts", ".tsx")
    for path in sorted(ROOT.rglob("*")):
        if path.suffix.lower() not in exts or "node_modules" in path.parts:
            continue
        raw = path.read_bytes()
        new = fix_bytes_all(raw)
        changed = new != raw
        t = new.decode("utf-8", errors="replace")
        ot = t
        for a, b in PHRASES:
            t = t.replace(a, b)
        if t != ot or changed:
            path.write_bytes(t.encode("utf-8"))
            print("OK", path.relative_to(ROOT.parent))


if __name__ == "__main__":
    main()

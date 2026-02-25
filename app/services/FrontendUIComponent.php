<?php

declare(strict_types=1);

namespace app\services;

/**
 * 前端UI组件类
 * 
 * 封装常用的前端UI逻辑，提供统一的UI组件生成方法
 * 使用和后台相同的设计系统（nt-* 类名）
 * 优化前端UI与后端类的交互
 */
class FrontendUIComponent
{
    /**
     * 组件前缀（与后台保持一致）
     */
    private const PREFIX = 'nt';
    /**
     * 生成分页HTML（使用后台设计系统）
     *
     * @param array $pagination 分页数据
     * @param string $baseUrl 基础URL
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderPagination(
        array $pagination,
        string $baseUrl = '',
        array $options = []
    ): string {
        $page = $pagination['page'] ?? 1;
        $totalPages = $pagination['total_pages'] ?? 1;
        $total = $pagination['total'] ?? 0;
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $prefix = self::PREFIX;
        $class = $options['class'] ?? "{$prefix}-pagination";
        $showInfo = $options['show_info'] ?? true;
        
        $html = '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
        
        if ($showInfo) {
            $html .= '<div class="' . $prefix . '-pagination-info">';
            $html .= '共 ' . $total . ' 条记录，第 ' . $page . ' / ' . $totalPages . ' 页';
            $html .= '</div>';
        }
        
        $html .= '<div class="' . $prefix . '-pagination-links">';
        
        // 上一页
        if ($page > 1) {
            $prevUrl = self::buildPaginationUrl($baseUrl, $page - 1, $options);
            $html .= '<a href="' . htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') . '" class="' . $prefix . '-btn ' . $prefix . '-btn-secondary">上一页</a>';
        } else {
            $html .= '<span class="' . $prefix . '-btn ' . $prefix . '-btn-secondary" disabled>上一页</span>';
        }
        
        // 页码
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        
        if ($start > 1) {
            $html .= '<a href="' . htmlspecialchars(self::buildPaginationUrl($baseUrl, 1, $options), ENT_QUOTES, 'UTF-8') . '" class="' . $prefix . '-btn ' . $prefix . '-btn-secondary">1</a>';
            if ($start > 2) {
                $html .= '<span class="' . $prefix . '-pagination-ellipsis">...</span>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $page) {
                $html .= '<span class="' . $prefix . '-btn ' . $prefix . '-btn-primary">' . $i . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars(self::buildPaginationUrl($baseUrl, $i, $options), ENT_QUOTES, 'UTF-8') . '" class="' . $prefix . '-btn ' . $prefix . '-btn-secondary">' . $i . '</a>';
            }
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<span class="' . $prefix . '-pagination-ellipsis">...</span>';
            }
            $html .= '<a href="' . htmlspecialchars(self::buildPaginationUrl($baseUrl, $totalPages, $options), ENT_QUOTES, 'UTF-8') . '" class="' . $prefix . '-btn ' . $prefix . '-btn-secondary">' . $totalPages . '</a>';
        }
        
        // 下一页
        if ($page < $totalPages) {
            $nextUrl = self::buildPaginationUrl($baseUrl, $page + 1, $options);
            $html .= '<a href="' . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . '" class="' . $prefix . '-btn ' . $prefix . '-btn-secondary">下一页</a>';
        } else {
            $html .= '<span class="' . $prefix . '-btn ' . $prefix . '-btn-secondary" disabled>下一页</span>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * 构建分页URL
     *
     * @param string $baseUrl 基础URL
     * @param int $page 页码
     * @param array $options 选项
     * @return string
     */
    private static function buildPaginationUrl(string $baseUrl, int $page, array $options): string
    {
        $paramName = $options['param_name'] ?? 'page';
        
        if (empty($baseUrl)) {
            $baseUrl = $_SERVER['REQUEST_URI'] ?? '/';
        }
        
        // 移除现有page参数
        $url = preg_replace('/[?&]' . preg_quote($paramName, '/') . '=\d+/', '', $baseUrl);
        
        // 添加新page参数
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url .= $separator . $paramName . '=' . $page;
        
        return $url;
    }

    /**
     * 生成表格HTML（使用后台设计系统）
     *
     * @param array $headers 表头
     * @param array $rows 数据行
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderTable(array $headers, array $rows, array $options = []): string
    {
        $prefix = self::PREFIX;
        $class = $options['class'] ?? "{$prefix}-table";
        $id = $options['id'] ?? '';
        $emptyText = $options['empty_text'] ?? '暂无数据';
        $containerClass = $options['container_class'] ?? "{$prefix}-table-container";
        
        $html = '<div class="' . htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<table';
        if ($id) {
            $html .= ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
        }
        $html .= ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
        
        // 表头
        if (!empty($headers)) {
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $text = is_array($header) ? ($header['text'] ?? '') : $header;
                $html .= '<th>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            $html .= '</tr></thead>';
        }
        
        // 表体
        $html .= '<tbody>';
        if (empty($rows)) {
            $colspan = count($headers);
            $html .= '<tr><td colspan="' . $colspan . '" class="' . $prefix . '-text-center ' . $prefix . '-text-muted">' . htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . (is_scalar($cell) ? htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') : '') . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * 生成表单字段HTML（使用后台设计系统）
     *
     * @param string $name 字段名
     * @param string $label 标签
     * @param string $type 类型
     * @param mixed $value 值
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderFormField(
        string $name,
        string $label,
        string $type = 'text',
        $value = '',
        array $options = []
    ): string {
        $prefix = self::PREFIX;
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $help = $options['help'] ?? '';
        $error = $options['error'] ?? '';
        $class = $options['class'] ?? '';
        
        $html = '<div class="' . $prefix . '-form-item' . ($error ? ' ' . $prefix . '-form-item-error' : '') . '">';
        
        // 标签
        $html .= '<label class="' . $prefix . '-form-label" for="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
        $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        if ($required) {
            $html .= ' <span class="' . $prefix . '-text-danger">*</span>';
        }
        $html .= '</label>';
        
        // 输入框
        $inputClass = $prefix . '-input';
        if ($class) {
            $inputClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        }
        if ($error) {
            $inputClass .= ' ' . $prefix . '-input-error';
        }
        
        $html .= '<input type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' class="' . $inputClass . '"';
        if ($placeholder) {
            $html .= ' placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        $html .= '>';
        
        // 帮助文本
        if ($help) {
            $html .= '<div class="' . $prefix . '-form-help">' . htmlspecialchars($help, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        // 错误信息
        if ($error) {
            $html .= '<div class="' . $prefix . '-form-error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * 生成消息提示HTML（使用后台设计系统）
     *
     * @param string $message 消息
     * @param string $type 类型 (success, error, warning, info)
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderAlert(string $message, string $type = 'info', array $options = []): string
    {
        $prefix = self::PREFIX;
        $dismissible = $options['dismissible'] ?? false;
        $class = $options['class'] ?? '';
        $icon = $options['icon'] ?? true;
        
        $html = '<div class="' . $prefix . '-alert ' . $prefix . '-alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        if ($class) {
            $html .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        }
        $html .= '">';
        
        // 图标
        if ($icon) {
            $html .= '<div class="' . $prefix . '-alert-icon">';
            $html .= self::getAlertIcon($type);
            $html .= '</div>';
        }
        
        $html .= '<div class="' . $prefix . '-alert-content">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
        
        if ($dismissible) {
            $html .= '<button type="button" class="' . $prefix . '-alert-close" aria-label="关闭">&times;</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * 获取提示图标SVG
     *
     * @param string $type 类型
     * @return string SVG代码
     */
    private static function getAlertIcon(string $type): string
    {
        $icons = [
            'success' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'error' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'warning' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'info' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
        ];
        
        return $icons[$type] ?? $icons['info'];
    }

    /**
     * 生成空状态HTML（使用后台设计系统）
     *
     * @param string $message 消息
     * @param string $icon 图标（SVG或emoji）
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderEmptyState(string $message, string $icon = '', array $options = []): string
    {
        $prefix = self::PREFIX;
        $actionText = $options['action_text'] ?? '';
        $actionUrl = $options['action_url'] ?? '';
        $class = $options['class'] ?? '';
        $title = $options['title'] ?? '';
        
        $html = '<div class="' . $prefix . '-empty-state';
        if ($class) {
            $html .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        }
        $html .= '">';
        
        if ($icon) {
            $html .= '<div class="' . $prefix . '-empty-illustration">';
            $html .= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
            $html .= '</div>';
        }
        
        if ($title) {
            $html .= '<h3 class="' . $prefix . '-empty-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
        }
        
        $html .= '<p class="' . $prefix . '-empty-desc">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        
        if ($actionText && $actionUrl) {
            $html .= '<a href="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '" class="' . $prefix . '-btn ' . $prefix . '-btn-primary">';
            $html .= htmlspecialchars($actionText, ENT_QUOTES, 'UTF-8');
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * 生成按钮HTML（使用后台设计系统）
     *
     * @param string $text 按钮文本
     * @param string $url 链接地址（可选）
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderButton(string $text, string $url = '', array $options = []): string
    {
        $prefix = self::PREFIX;
        $type = $options['type'] ?? 'primary'; // primary, secondary
        $icon = $options['icon'] ?? '';
        $class = $options['class'] ?? '';
        $onclick = $options['onclick'] ?? '';
        
        $btnClass = $prefix . '-btn ' . $prefix . '-btn-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        if ($class) {
            $btnClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        }
        
        $tag = empty($url) ? 'button' : 'a';
        $html = '<' . $tag;
        
        if ($tag === 'a') {
            $html .= ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
        } else {
            $html .= ' type="' . ($options['button_type'] ?? 'button') . '"';
        }
        
        $html .= ' class="' . $btnClass . '"';
        
        if ($onclick) {
            $html .= ' onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        $html .= '>';
        
        if ($icon) {
            $html .= $icon . ' ';
        }
        
        $html .= htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $html .= '</' . $tag . '>';
        
        return $html;
    }

    /**
     * 生成卡片HTML（使用后台设计系统）
     *
     * @param string $content 内容
     * @param array $options 选项
     * @return string HTML代码
     */
    public static function renderCard(string $content, array $options = []): string
    {
        $prefix = self::PREFIX;
        $title = $options['title'] ?? '';
        $class = $options['class'] ?? '';
        $header = $options['header'] ?? '';
        $footer = $options['footer'] ?? '';
        
        $html = '<div class="' . $prefix . '-card';
        if ($class) {
            $html .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        }
        $html .= '">';
        
        if ($header || $title) {
            $html .= '<div class="' . $prefix . '-card-header">';
            if ($title) {
                $html .= '<h3 class="' . $prefix . '-card-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
            }
            if ($header) {
                $html .= $header;
            }
            $html .= '</div>';
        }
        
        $html .= '<div class="' . $prefix . '-card-body">';
        $html .= $content;
        $html .= '</div>';
        
        if ($footer) {
            $html .= '<div class="' . $prefix . '-card-footer">';
            $html .= $footer;
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

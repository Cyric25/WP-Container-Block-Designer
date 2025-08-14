<?php
/**
 * Container Block Designer - Blocks List Template
 * Version: 2.2.1 - Fixed column names
 * 
 * @package ContainerBlockDesigner
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get all blocks - FIXED: Using 'created' instead of 'created_at'
global $wpdb;
$blocks = $wpdb->get_results("SELECT * FROM " . CBD_TABLE_BLOCKS . " ORDER BY created DESC");

// Statistics
$total_blocks = count($blocks);
$active_blocks = 0;
$inactive_blocks = 0;

foreach ($blocks as $block) {
    if ($block->status === 'active') {
        $active_blocks++;
    } else {
        $inactive_blocks++;
    }
}
?>

<div class="cbd-blocks-overview">
    <!-- Header with Stats -->
    <div class="cbd-overview-header">
        <h2><?php echo esc_html__('Container Blocks Übersicht', 'container-block-designer'); ?></h2>
        
        <div class="cbd-stats">
            <div class="cbd-stat-item">
                <span class="cbd-stat-number"><?php echo esc_html($total_blocks); ?></span>
                <span class="cbd-stat-label"><?php echo esc_html__('Gesamt', 'container-block-designer'); ?></span>
            </div>
            <div class="cbd-stat-item">
                <span class="cbd-stat-number"><?php echo esc_html($active_blocks); ?></span>
                <span class="cbd-stat-label"><?php echo esc_html__('Aktiv', 'container-block-designer'); ?></span>
            </div>
            <div class="cbd-stat-item">
                <span class="cbd-stat-number"><?php echo esc_html($inactive_blocks); ?></span>
                <span class="cbd-stat-label"><?php echo esc_html__('Inaktiv', 'container-block-designer'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Actions Bar -->
    <div class="cbd-actions-bar">
        <div class="cbd-actions-left">
            <a href="<?php echo admin_url('admin.php?page=container-block-designer&action=new'); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php echo esc_html__('Neuer Block', 'container-block-designer'); ?>
            </a>
            
            <?php if ($total_blocks > 0): ?>
            <button type="button" class="button" id="cbd-bulk-actions-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Massenaktionen', 'container-block-designer'); ?>
            </button>
            
            <div id="cbd-bulk-actions-menu" class="cbd-dropdown-menu" style="display: none;">
                <a href="#" data-action="activate"><?php echo esc_html__('Aktivieren', 'container-block-designer'); ?></a>
                <a href="#" data-action="deactivate"><?php echo esc_html__('Deaktivieren', 'container-block-designer'); ?></a>
                <a href="#" data-action="delete" class="cbd-danger"><?php echo esc_html__('Löschen', 'container-block-designer'); ?></a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_blocks > 0): ?>
        <div class="cbd-actions-right">
            <input type="text" id="cbd-search" placeholder="<?php echo esc_attr__('Blocks durchsuchen...', 'container-block-designer'); ?>" class="regular-text">
            
            <select id="cbd-filter-status" class="postform">
                <option value=""><?php echo esc_html__('Alle Status', 'container-block-designer'); ?></option>
                <option value="active"><?php echo esc_html__('Aktiv', 'container-block-designer'); ?></option>
                <option value="inactive"><?php echo esc_html__('Inaktiv', 'container-block-designer'); ?></option>
                <option value="draft"><?php echo esc_html__('Entwurf', 'container-block-designer'); ?></option>
            </select>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Blocks Table -->
    <?php if ($total_blocks > 0): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="check-column">
                    <input type="checkbox" id="cbd-select-all">
                </td>
                <th class="column-name"><?php echo esc_html__('Name', 'container-block-designer'); ?></th>
                <th class="column-slug"><?php echo esc_html__('Slug', 'container-block-designer'); ?></th>
                <th class="column-description"><?php echo esc_html__('Beschreibung', 'container-block-designer'); ?></th>
                <th class="column-status"><?php echo esc_html__('Status', 'container-block-designer'); ?></th>
                <th class="column-created"><?php echo esc_html__('Erstellt', 'container-block-designer'); ?></th>
            </tr>
        </thead>
        <tbody id="cbd-blocks-tbody">
            <?php foreach ($blocks as $block): 
                $features = json_decode($block->features, true) ?: [];
            ?>
            <tr data-status="<?php echo esc_attr($block->status); ?>">
                <th scope="row" class="check-column">
                    <input type="checkbox" class="cbd-block-checkbox" value="<?php echo esc_attr($block->id); ?>">
                </th>
                <td class="column-name">
                    <strong>
                        <a href="<?php echo admin_url('admin.php?page=container-block-designer&action=edit&id=' . $block->id); ?>">
                            <?php echo esc_html($block->name); ?>
                        </a>
                    </strong>
                    
                    <!-- Features Icons -->
                    <?php if (!empty($features)): ?>
                    <div class="cbd-features-icons">
                        <?php foreach ($features as $feature_key => $feature): ?>
                            <?php if (!empty($feature['enabled'])): ?>
                                <span class="cbd-feature-icon" title="<?php echo esc_attr(ucfirst($feature_key)); ?>">
                                    <?php
                                    switch($feature_key) {
                                        case 'icon':
                                            echo '<span class="dashicons dashicons-star-filled"></span>';
                                            break;
                                        case 'collapse':
                                            echo '<span class="dashicons dashicons-arrow-down-alt2"></span>';
                                            break;
                                        case 'numbering':
                                            echo '<span class="dashicons dashicons-editor-ol"></span>';
                                            break;
                                        case 'copyText':
                                            echo '<span class="dashicons dashicons-clipboard"></span>';
                                            break;
                                        case 'screenshot':
                                            echo '<span class="dashicons dashicons-camera"></span>';
                                            break;
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row-actions">
                        <span class="edit">
                            <a href="<?php echo admin_url('admin.php?page=container-block-designer&action=edit&id=' . $block->id); ?>">
                                <?php echo esc_html__('Bearbeiten', 'container-block-designer'); ?>
                            </a> |
                        </span>
                        <span class="duplicate">
                            <a href="#" class="cbd-duplicate-btn" data-id="<?php echo esc_attr($block->id); ?>">
                                <?php echo esc_html__('Duplizieren', 'container-block-designer'); ?>
                            </a> |
                        </span>
                        <span class="toggle-status">
                            <a href="#" class="cbd-toggle-status-btn" data-id="<?php echo esc_attr($block->id); ?>" data-status="<?php echo esc_attr($block->status); ?>">
                                <?php echo $block->status === 'active' ? esc_html__('Deaktivieren', 'container-block-designer') : esc_html__('Aktivieren', 'container-block-designer'); ?>
                            </a> |
                        </span>
                        <span class="trash">
                            <a href="#" class="cbd-delete-btn" data-id="<?php echo esc_attr($block->id); ?>" data-name="<?php echo esc_attr($block->name); ?>">
                                <?php echo esc_html__('Löschen', 'container-block-designer'); ?>
                            </a>
                        </span>
                    </div>
                </td>
                <td class="column-slug">
                    <code><?php echo esc_html($block->slug); ?></code>
                </td>
                <td class="column-description">
                    <?php echo esc_html($block->description ?: '-'); ?>
                </td>
                <td class="column-status">
                    <span class="cbd-status-badge <?php echo esc_attr($block->status); ?>">
                        <?php 
                        switch($block->status) {
                            case 'active':
                                echo esc_html__('Aktiv', 'container-block-designer');
                                break;
                            case 'inactive':
                                echo esc_html__('Inaktiv', 'container-block-designer');
                                break;
                            case 'draft':
                                echo esc_html__('Entwurf', 'container-block-designer');
                                break;
                            default:
                                echo esc_html($block->status);
                        }
                        ?>
                    </span>
                </td>
                <td class="column-created">
                    <?php 
                    // FIXED: Using 'created' instead of 'created_at'
                    if (!empty($block->created)) {
                        $created_date = mysql2date('d.m.Y', $block->created);
                        $created_time = mysql2date('H:i', $block->created);
                    ?>
                        <span title="<?php echo esc_attr($created_time); ?>">
                            <?php echo esc_html($created_date); ?>
                        </span>
                    <?php } else { ?>
                        <span>-</span>
                    <?php } ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php else: ?>
    
    <!-- Empty State -->
    <div class="cbd-empty-state">
        <div class="cbd-empty-icon">
            <span class="dashicons dashicons-layout"></span>
        </div>
        <h3><?php echo esc_html__('Keine Container Blocks vorhanden', 'container-block-designer'); ?></h3>
        <p><?php echo esc_html__('Erstellen Sie Ihren ersten Container Block, um loszulegen.', 'container-block-designer'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=container-block-designer&action=new'); ?>" class="button button-primary button-hero">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php echo esc_html__('Ersten Block erstellen', 'container-block-designer'); ?>
        </a>
    </div>
    
    <?php endif; ?>
</div>

<style>
.cbd-blocks-overview {
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.cbd-overview-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.cbd-overview-header h2 {
    margin: 0 0 20px 0;
    font-size: 24px;
    color: #1d2327;
}

.cbd-stats {
    display: flex;
    gap: 30px;
}

.cbd-stat-item {
    display: flex;
    flex-direction: column;
}

.cbd-stat-number {
    font-size: 36px;
    font-weight: 600;
    color: #2271b1;
    line-height: 1;
}

.cbd-stat-label {
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}

.cbd-actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f6f7f7;
    border-radius: 4px;
}

.cbd-actions-left,
.cbd-actions-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.cbd-dropdown-menu {
    position: absolute;
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
    z-index: 10;
    margin-top: 5px;
}

.cbd-dropdown-menu a {
    display: block;
    padding: 8px 15px;
    text-decoration: none;
    color: #2c3338;
}

.cbd-dropdown-menu a:hover {
    background: #f0f0f1;
}

.cbd-dropdown-menu a.cbd-danger {
    color: #d63638;
}

.cbd-status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.cbd-status-badge.active {
    background: #d4f4dd;
    color: #00a32a;
}

.cbd-status-badge.inactive {
    background: #fcf0f1;
    color: #d63638;
}

.cbd-status-badge.draft {
    background: #f0f6fc;
    color: #0073aa;
}

.cbd-features-icons {
    display: inline-flex;
    gap: 5px;
    margin-left: 10px;
}

.cbd-feature-icon {
    display: inline-block;
    color: #787c82;
    font-size: 16px;
}

.cbd-feature-icon:hover {
    color: #2271b1;
}

.cbd-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.cbd-empty-icon {
    font-size: 48px;
    color: #dcdcde;
    margin-bottom: 20px;
}

.cbd-empty-state h3 {
    font-size: 20px;
    margin: 0 0 10px;
    color: #1d2327;
}

.cbd-empty-state p {
    color: #646970;
    margin-bottom: 20px;
}

.button-hero {
    font-size: 16px !important;
    padding: 8px 20px !important;
    height: auto !important;
}

.column-name .row-actions {
    visibility: hidden;
}

.column-name:hover .row-actions {
    visibility: visible;
}
</style>
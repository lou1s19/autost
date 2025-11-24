<?php
/**
 * CSS-Styles für Admin-Seite
 */
?>
<style>
    .asu-setup-wrapper {
        max-width: 1200px;
        margin: 20px auto;
    }
    .asu-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .asu-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
        font-weight: 600;
    }
    .asu-header p {
        margin: 0;
        font-size: 16px;
        opacity: 0.9;
    }
    .asu-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .asu-card h2 {
        margin-top: 0;
        font-size: 24px;
        color: #333;
        border-bottom: 2px solid #667eea;
        padding-bottom: 10px;
    }
    .asu-card p {
        color: #666;
        line-height: 1.6;
    }
    .asu-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 5px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 15px;
    }
    .asu-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    .asu-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .asu-button.secondary {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .asu-status {
        margin-top: 15px;
        padding: 15px;
        border-radius: 5px;
        display: none;
    }
    .asu-status.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .asu-status.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .asu-status.info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .asu-plugin-list {
        margin-top: 20px;
    }
    .asu-plugin-item {
        display: flex;
        align-items: center;
        padding: 15px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        transition: background 0.2s;
    }
    .asu-plugin-item:hover {
        background: #e9ecef;
    }
    .asu-plugin-item input[type="checkbox"] {
        margin-right: 15px;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .asu-plugin-item label {
        flex: 1;
        cursor: pointer;
        font-weight: 500;
        color: #333;
    }
    .asu-plugin-item .asu-plugin-desc {
        color: #666;
        font-size: 14px;
        margin-left: 35px;
    }
    .asu-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
        margin-left: 10px;
        vertical-align: middle;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .asu-feature-list {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    .asu-feature-list li {
        padding: 10px 0;
        padding-left: 30px;
        position: relative;
    }
    .asu-feature-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #667eea;
        font-weight: bold;
        font-size: 18px;
    }
</style>


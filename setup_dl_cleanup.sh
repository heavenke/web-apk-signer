#!/bin/bash

# 定义要添加的 crontab 任务
CRON_TASK="* * * * * find /www/wwwroot/signroot.heavenke.cn/dl -type f \( -name \"*.apk\" -o -name \"*.jar\" \) -mmin +60 -delete 2>/dev/null"

# 获取当前用户的 crontab 内容
CURRENT_CRON=$(crontab -l 2>/dev/null)

# 检查任务是否已存在
if echo "$CURRENT_CRON" | grep -Fq "$CRON_TASK"; then
    echo "任务已存在，无需添加。"
else
    # 将新任务追加到 crontab 中
    (echo "$CURRENT_CRON"; echo "$CRON_TASK") | crontab -
    echo "任务已成功添加到 crontab。"
fi

UPDATE lh_chat SET chat_duration = (SELECT MAX(lh_msg.time) FROM lh_msg WHERE lh_msg.chat_id = lh_chat.id AND lh_msg.user_id = 0)-(lh_chat.time+lh_chat.wait_time);
UPDATE lh_chat SET chat_duration = 0 WHERE chat_duration < 0;
-- Conversations + bots (auto-replies) migration
-- Run this once in phpMyAdmin (Database: chat) after you already have `users` and `messages`.

-- 1) Bots table (separate from users)
CREATE TABLE IF NOT EXISTS bots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  reply_template TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed bots (safe-ish: ignore duplicates)
INSERT IGNORE INTO bots (slug, name, reply_template)
VALUES
  ('ahmed', 'Ahmed', 'Hey! Thanks for your message. Talk to me more!'),
  ('islam', 'Islam', 'Hey! I am Islam. Tell me more about your day!'),
  ('ferhat', 'Ferhat', 'Hey! I am Ferhat. What are you working on today?');

-- 2) Conversations
CREATE TABLE IF NOT EXISTS conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NULL,
  created_by_user_id INT NOT NULL,
  bot_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_conversations_created_by
    FOREIGN KEY (created_by_user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_conversations_bot
    FOREIGN KEY (bot_id) REFERENCES bots(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Conversation members (many-to-many)
CREATE TABLE IF NOT EXISTS conversation_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_conversation_member (conversation_id, user_id),
  KEY idx_conversation_members_user (user_id),
  CONSTRAINT fk_conversation_members_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_conversation_members_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Helpful indexes
ALTER TABLE messages
  ADD KEY idx_messages_conversation_created (conversation_id, created_at),
  ADD KEY idx_messages_user_created (user_id, created_at);

-- 5) Foreign keys
-- If you already added this FK manually, this line will fail; in that case, skip it.
ALTER TABLE messages
  ADD CONSTRAINT fk_messages_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE;

ALTER TABLE messages
  ADD CONSTRAINT fk_messages_conversation
    FOREIGN KEY (conversation_id) REFERENCES conversations(id)
    ON DELETE CASCADE;

-- 6) Backfill: create 1 conversation per existing user_id (for existing per-user history)
-- Pick Ahmed as the default bot for backfilled conversations.
INSERT INTO conversations (title, created_by_user_id, bot_id, created_at)
SELECT
  'Chat with Ahmed' AS title,
  m.user_id AS created_by_user_id,
  (SELECT b.id FROM bots b WHERE b.slug = 'ahmed' LIMIT 1) AS bot_id,
  MIN(m.created_at) AS created_at
FROM messages m
WHERE m.user_id IS NOT NULL
GROUP BY m.user_id;

INSERT IGNORE INTO conversation_members (conversation_id, user_id, created_at)
SELECT c.id, c.created_by_user_id, c.created_at
FROM conversations c;

UPDATE messages m
JOIN conversations c ON c.created_by_user_id = m.user_id
SET m.conversation_id = c.id
WHERE m.user_id IS NOT NULL AND m.conversation_id IS NULL;


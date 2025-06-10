import React from 'react';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

const ChatListItem = ({ chat, onClick }) => {
  const { name, avatar, lastMessage, unreadCount, isOnline, lastSeen } = chat;

  return (
    <div
      onClick={onClick}
      className="flex items-center px-3 py-3 hover:bg-dark-lightest cursor-pointer transition-colors"
    >
      {}
      <div className="relative">
        <img
          src={avatar}
          alt={name}
          className="w-12 h-12 rounded-full object-cover"
        />
        {isOnline && (
          <span className="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-500 ring-2 ring-dark-lighter" />
        )}
      </div>

      {}
      <div className="flex-1 min-w-0 mr-4">
        <div className="flex justify-between items-baseline">
          <h3 className="text-base font-medium truncate">{name}</h3>
          <span className="text-xs text-gray-400">
            {lastMessage && format(new Date(lastMessage.timestamp), 'p', { locale: ar })}
          </span>
        </div>

        <div className="flex justify-between items-center">
          <p className="text-sm text-gray-400 truncate">
            {lastMessage?.text || 'ابدأ المحادثة'}
          </p>
          
          {unreadCount > 0 && (
            <span className="ml-2 bg-primary px-1.5 py-0.5 rounded-full text-[10px] font-semibold min-w-[18px] text-center">
              {unreadCount}
            </span>
          )}
        </div>
      </div>
    </div>
  );
};

export default ChatListItem; 
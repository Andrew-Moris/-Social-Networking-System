import React, { useState } from 'react';
import { MagnifyingGlassIcon, SunIcon, MoonIcon } from '@heroicons/react/24/outline';
import ChatListItem from './ChatListItem';
import { useChat } from '../context/ChatContext';

const Sidebar = ({ isOpen, onClose, isDarkMode, onToggleDarkMode }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const { chats, setActiveChat } = useChat();

  return (
    <>
      {}
      {isOpen && (
        <div 
          className="fixed inset-0 bg-black bg-opacity-50 lg:hidden z-20"
          onClick={onClose}
        />
      )}

      {}
      <div className={`
        fixed lg:static inset-y-0 right-0 w-80 bg-dark-lighter
        transform transition-transform duration-300 ease-in-out z-30
        ${isOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'}
      `}>
        {}
        <div className="h-16 bg-dark-lightest flex items-center px-4 justify-between">
          <h1 className="text-lg font-semibold">واتساب ويب</h1>
          <button
            onClick={onToggleDarkMode}
            className="p-2 hover:bg-dark-lighter rounded-full"
          >
            {isDarkMode ? (
              <SunIcon className="w-6 h-6" />
            ) : (
              <MoonIcon className="w-6 h-6" />
            )}
          </button>
        </div>

        {}
        <div className="p-2">
          <div className="relative">
            <MagnifyingGlassIcon className="absolute right-3 top-2.5 w-5 h-5 text-gray-400" />
            <input
              type="text"
              placeholder="بحث في المحادثات"
              className="w-full bg-dark-lightest rounded-lg pl-4 pr-10 py-2 focus:outline-none focus:ring-1 focus:ring-primary"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>
        </div>

        {}
        <div className="overflow-y-auto h-[calc(100vh-8rem)]">
          {chats
            .filter(chat => 
              chat.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
              chat.lastMessage?.text.toLowerCase().includes(searchQuery.toLowerCase())
            )
            .map(chat => (
              <ChatListItem
                key={chat.id}
                chat={chat}
                onClick={() => {
                  setActiveChat(chat);
                  onClose();
                }}
              />
            ))
          }
        </div>
      </div>
    </>
  );
};

export default Sidebar; 
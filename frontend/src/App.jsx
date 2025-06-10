import React, { useState } from 'react';
import Sidebar from './components/Sidebar';
import ChatArea from './components/ChatArea';
import { ChatProvider } from './context/ChatContext';

function App() {
  const [isDarkMode, setIsDarkMode] = useState(true);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  return (
    <ChatProvider>
      <div dir="rtl" className={`h-screen flex ${isDarkMode ? 'dark' : ''}`}>
        <div className="flex h-full w-full bg-[#0f1121] text-gray-100">
          <Sidebar 
            isOpen={isSidebarOpen}
            onClose={() => setIsSidebarOpen(false)}
            isDarkMode={isDarkMode}
            onToggleDarkMode={() => setIsDarkMode(!isDarkMode)}
          />
          
          <ChatArea 
            onOpenSidebar={() => setIsSidebarOpen(true)}
          />
        </div>
      </div>
    </ChatProvider>
  );
}

export default App; 
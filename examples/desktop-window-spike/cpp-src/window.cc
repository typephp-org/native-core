#include <phpx.h>
#include <windows.h>

using namespace php;

static bool g_running = true;

LRESULT CALLBACK NativeCoreWindowProc(HWND window, UINT message, WPARAM wParam, LPARAM lParam)
{
    if (message == WM_CLOSE) {
        DestroyWindow(window);
        return 0;
    }
    if (message == WM_DESTROY) {
        g_running = false;
        PostQuitMessage(0);
        return 0;
    }
    return DefWindowProc(window, message, wParam, lParam);
}

Int php_win_create_window(String title, Int width, Int height)
{
    g_running = true;
    HINSTANCE instance = GetModuleHandle(nullptr);
    WNDCLASS windowClass = {};
    windowClass.lpfnWndProc = NativeCoreWindowProc;
    windowClass.hInstance = instance;
    windowClass.hCursor = LoadCursor(nullptr, IDC_ARROW);
    windowClass.hbrBackground = reinterpret_cast<HBRUSH>(COLOR_WINDOW + 1);
    windowClass.lpszClassName = "TypePHPNativeCoreSpike";
    RegisterClass(&windowClass);

    HWND window = CreateWindowEx(
        0,
        windowClass.lpszClassName,
        title.data(),
        WS_OVERLAPPEDWINDOW,
        CW_USEDEFAULT,
        CW_USEDEFAULT,
        static_cast<int>(width),
        static_cast<int>(height),
        nullptr,
        nullptr,
        instance,
        nullptr
    );
    return static_cast<Int>(reinterpret_cast<intptr_t>(window));
}

void php_win_show_window(Int handle)
{
    HWND window = reinterpret_cast<HWND>(static_cast<intptr_t>(handle));
    ShowWindow(window, SW_SHOW);
    UpdateWindow(window);
}

Bool php_win_pump_events()
{
    MSG message = {};
    while (PeekMessage(&message, nullptr, 0, 0, PM_REMOVE)) {
        if (message.message == WM_QUIT) {
            g_running = false;
            break;
        }
        TranslateMessage(&message);
        DispatchMessage(&message);
    }
    return g_running;
}

void php_win_close_window(Int handle)
{
    HWND window = reinterpret_cast<HWND>(static_cast<intptr_t>(handle));
    if (window != nullptr) {
        PostMessage(window, WM_CLOSE, 0, 0);
    }
}

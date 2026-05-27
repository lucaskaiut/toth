import { apiRequest } from "~/lib/api/client";
import type {
  ApiResponse,
  Conversation,
  ConversationAttendanceStatus,
  Message,
} from "~/types/crm";

export const conversationsApi = {
  list() {
    return apiRequest<ApiResponse<Conversation[]>>("/conversations");
  },

  messages(conversationId: number) {
    return apiRequest<ApiResponse<Message[]>>(
      `/conversations/${conversationId}/messages`,
    );
  },

  sendMessage(conversationId: number, content: string) {
    return apiRequest<ApiResponse<Message>>(
      `/conversations/${conversationId}/messages`,
      {
        method: "POST",
        body: { content },
      },
    );
  },

  updateAttendanceStatus(
    conversationId: number,
    attendanceStatus: ConversationAttendanceStatus,
  ) {
    return apiRequest<ApiResponse<Conversation>>(
      `/conversations/${conversationId}/attendance-status`,
      {
        method: "PATCH",
        body: { attendance_status: attendanceStatus },
      },
    );
  },
};

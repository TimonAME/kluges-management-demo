import { ref } from "vue";
import { debounce } from "lodash";
import axios from "axios";

/**
 * Composable for managing event users and user search functionality
 * @param {Object} options - Configuration options
 * @param {number} options.currentUserId - ID of the current user
 * @param {Function} options.onUsersChange - Callback when users change
 * @returns {Object} Event handling methods and state
 */
export default function useEventUsers({ currentUserId, onUsersChange }) {
  // State
  const searchQuery = ref("");
  const searchResults = ref([]);
  const isSearching = ref(false);
  const selectedUsers = ref([]);

  /**
   * Debounced search function
   */
  const searchUsers = debounce(async (query) => {
    if (!query) {
      searchResults.value = [];
      isSearching.value = false;
      return;
    }

    try {
      isSearching.value = true;
      const response = await axios.get("/api/users", {
        params: { search: query },
      });

      // Handle different response structures
      let users = Array.isArray(response.data)
        ? response.data
        : response.data.users
          ? response.data.users
          : response.data.data
            ? response.data.data
            : [];

      // Filter out already selected users and current user
      searchResults.value = users.filter(
        (user) =>
          user && // Ensure user object exists
          !selectedUsers.value.some((selected) => selected.id === user.id) &&
          user.id !== currentUserId,
      );
    } catch (error) {
      console.error("Error searching users:", error);
      searchResults.value = [];
    } finally {
      isSearching.value = false;
    }
  }, 300);

  /**
   * Handle user search input
   * @param {Event} event - Input event
   */
  const handleUserSearch = (event) => {
    const query =
      typeof event === "string" ? event : event?.target?.value || "";
    searchQuery.value = query;
    searchUsers(query);
  };

  /**
   * Add a user to selected users
   * @param {Object} user - User to add
   */
  const addUser = (user) => {
    if (user && !selectedUsers.value.some((u) => u.id === user.id)) {
      selectedUsers.value.push(user);
      onUsersChange?.(selectedUsers.value);
    }
    searchQuery.value = "";
    searchResults.value = [];
  };

  /**
   * Remove a user from selected users
   * @param {Object} user - User to remove
   */
  const removeUser = (user) => {
    if (user) {
      selectedUsers.value = selectedUsers.value.filter((u) => u.id !== user.id);
      onUsersChange?.(selectedUsers.value);
    }
  };

  /**
   * Initialize selected users
   * @param {Array} users - Initial users
   */
  const initializeUsers = (users = []) => {
    selectedUsers.value = Array.isArray(users) ? users : [];
  };

  /**
   * Reset all user selections and search state
   */
  const resetUsers = () => {
    selectedUsers.value = [];
    searchQuery.value = "";
    searchResults.value = [];
    isSearching.value = false;
  };

  return {
    // State
    searchQuery,
    searchResults,
    isSearching,
    selectedUsers,

    // Methods
    handleUserSearch,
    addUser,
    removeUser,
    initializeUsers,
    resetUsers,
  };
}

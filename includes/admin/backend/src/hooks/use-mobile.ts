import * as React from "react"

export function useIsMobile(query: string = "(max-width: 768px)") {
  const [isMobile, setIsMobile] = React.useState(false)

  React.useEffect(() => {
    const mediaQuery = window.matchMedia(query)
    const handleChange = () => {
      setIsMobile(mediaQuery.matches)
    }

    // Initial check
    handleChange()

    // Listen for changes
    mediaQuery.addEventListener("change", handleChange)

    return () => {
      mediaQuery.removeEventListener("change", handleChange)
    }
  }, [query])

  return isMobile
} 
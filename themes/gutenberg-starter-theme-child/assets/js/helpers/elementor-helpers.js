'use strict'

/**
 *
 * @param {string} moduleSelector selector for module/section that we want to affect
 * @param {Array<string>|string} elementsSelectors single selector or an array of them for module's elements which we want to equal heights
 * @param {boolean} perRow equal heights of elements regarding only the displayed row they are in OR equal to the module's highest element. Default `true`
 */
// "DAR" in comments mean "delete after review". I've added them to make function flow easier to understand but they will be probably removed after approval
export function equalModuleElementsHeight(moduleSelector, elementsSelectors, perRow = true) {
  if (
    !moduleSelector ||
    !(Array.isArray(elementsSelectors) || typeof elementsSelectors === "string")
  ) {
    return;
  }

  if (typeof elementsSelectors === "string") {
    elementsSelectors = [elementsSelectors];
  }

  const module = document.querySelector(moduleSelector);
  const selectedElements = [];

  //DAR looping over given elements selectors and then finding all of them in given module
  elementsSelectors.forEach((elSel) => {
    /*DAR selectedElement structure looks like this:
        {
            rows: [
                {
                    nodes: [node1, node2, node3],
                    highestValue: 40
                },
                {
                    nodes: [node4, node5, node6],
                    highestValue: 55
                }
            ],
            globalHighestValue: 55
        }
    */
    const selectedElement = { rows: [], globalHighestValue: 0 };
    const els = module.querySelectorAll(elSel);
    let nodes = [];
    let highestValue = 0;

    //DAR looping over elements found with given selector
    els.forEach((el, index) => {
      const elRect = el.getBoundingClientRect();

      //DAR within each loop we check the globalHighestValue to avoid needing to loop over them again in case perRow = false
      if (selectedElement.globalHighestValue < elRect.height) {
        selectedElement.globalHighestValue = elRect.height;
      }

      //DAR first element is special case than the others (and the last one)
      if (index === 0) {
        nodes.push(el);
        highestValue = elRect.height;
      } else {
        if (els[index - 1].getBoundingClientRect().left < elRect.left) {
          //DAR element is in the same row as previous
          nodes.push(el);
          //DAR checking the row's highest value
          if (highestValue < elRect.height) {
            highestValue = elRect.height;
          }
        } else {
          //DAR element is in new row
          selectedElement.rows.push({ nodes, highestValue });
          nodes = [el];
          highestValue = elRect.height;
        }

        //DAR last element
        if (index === els.length - 1) {
          selectedElement.rows.push({ nodes, highestValue });
        }
      }
    });

    selectedElements.push(selectedElement);
  });

  selectedElements.forEach((element) => {
    element.rows.forEach((row) => {
      //DAR based on perRow argument we set elements' height with row's highest value or highest value in the whole module
      const height = perRow ? `${row.highestValue}px` : `${element.globalHighestValue}px`;
      row.nodes.forEach((node) => {
        node.style.height = height;
      });
    });
  });
}

export const accordion = {
  // event based solution. Works with any number of accordions
  _currentAccordion: null,

  /**
   * @param {string} selector proper css selector. Function will close accordions which are children of elements found by given selector
   */
  closeAllAccordions (selector = '') {
    if (typeof selector !== 'string') {
      return
    }
    document
      .querySelectorAll(
        `${selector} .elementor-accordion .elementor-tab-title.elementor-active`
      )
      .forEach((el) => {
        el.click()
      })
  },

  /**
   * @param {string} selector proper css selector
   * @description Function will add events listeners that restricts accordions to be open one at a time. Applies only to accordions that are children of elements found by given selector. Can be used multiple times to differentiate sections/modules
   */
  setAccordionsOpenSingle (selector = '') {
    if (typeof selector !== 'string') {
      return
    }
    const accordionChangedEvent = new Event('accordion:changed')
    const changeCurrentAccordion = (event) => {
      const clickedEl = event.currentTarget
      if (clickedEl.classList.contains('elementor-active')) {
        this._currentAccordion = clickedEl
        clickedEl.dispatchEvent(accordionChangedEvent)
      }
    }
    const customAttribute = 'data-gravity-accordion-open-single'
    document
      .querySelectorAll(`${selector} .elementor-accordion .elementor-tab-title`)
      .forEach((el) => {
        if (!el.hasAttribute(customAttribute)) {
          el.setAttribute(customAttribute, '')
          el.addEventListener('click', changeCurrentAccordion)
          el.addEventListener('accordion:changed', () => {
            document
              .querySelectorAll(
                `${selector} .elementor-accordion .elementor-tab-title.elementor-active`
              )
              .forEach((activeEl) => {
                if (!activeEl.isSameNode(this._currentAccordion)) {
                  activeEl.removeEventListener('click', changeCurrentAccordion)
                  activeEl.click()
                  activeEl.addEventListener('click', changeCurrentAccordion)
                }
              })
          })
        }
      })
  },
};